<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\HebronPayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request): JsonResponse
    {
        $payments = $this->payments->listForUser($request->user());

        return response()->json([
            'payments' => PaymentResource::collection($payments),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $payment = $this->payments->showForUser($request->user(), $id);

        return response()->json([
            'payment' => new PaymentResource($payment),
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->payments->create($request->user(), $request->validated());
        } catch (HebronPayException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 502);
        }

        return response()->json([
            'payment' => new PaymentResource($payment),
        ], 201);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        try {
            $payment = $this->payments->cancelForUser($request->user(), $id);
        } catch (HebronPayException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->status >= 400 && $e->status < 600 ? $e->status : 422);
        }

        return response()->json([
            'payment' => new PaymentResource($payment),
        ]);
    }
}
