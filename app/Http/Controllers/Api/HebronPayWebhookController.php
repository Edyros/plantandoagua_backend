<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HebronPayWebhookController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('hebronpay.webhook_secret');
        if ($secret !== '' && ! $this->signatureIsValid($request, $secret)) {
            return response()->json(['message' => 'Assinatura inválida.'], 401);
        }

        $payload = $request->json()->all();
        if ($payload === []) {
            $payload = $request->all();
        }

        try {
            $this->payments->handleWebhook(is_array($payload) ? $payload : []);
        } catch (\Throwable $e) {
            Log::error('HebronPay webhook failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Falha ao processar webhook.'], 500);
        }

        return response()->json(['received' => true]);
    }

    private function signatureIsValid(Request $request, string $secret): bool
    {
        $provided = $request->header('x-signature')
            ?? $request->header('x-webhook-signature')
            ?? $request->header('x-gatewee-signature');

        if (! is_string($provided) || $provided === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $provided)
            || hash_equals(hash_hmac('sha256', $request->getContent(), $secret, true), base64_decode($provided, true) ?: '');
    }
}
