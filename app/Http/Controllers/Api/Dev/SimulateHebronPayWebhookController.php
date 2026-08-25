<?php

namespace App\Http\Controllers\Api\Dev;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\HebronPayWebhookSimulator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SimulateHebronPayWebhookController extends Controller
{
    public function __construct(private HebronPayWebhookSimulator $simulator) {}

    public function index(): JsonResponse
    {
        $this->guardLocal();

        return response()->json([
            'fixtures' => $this->simulator->list(),
        ]);
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $this->guardLocal();

        $fixtures = $this->simulator->list();
        $validated = $request->validate([
            'fixture' => ['required', 'string', Rule::in($fixtures)],
        ]);

        /** @var Payment $payment */
        $payment = Payment::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $result = $this->simulator->replay($validated['fixture'], $payment);

        return response()->json([
            'received' => true,
            'fixture' => $result['fixture'],
            'webhook' => $result['payload'],
            'payment' => new PaymentResource($result['payment']),
        ]);
    }

    private function guardLocal(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
    }
}
