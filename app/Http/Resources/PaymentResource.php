<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => round($this->amount_cents / 100, 2),
            'amountCents' => $this->amount_cents,
            'currency' => $this->currency,
            'paymentMethod' => $this->payment_method,
            'status' => $this->status,
            'description' => $this->description,
            'pixCopyPaste' => $this->pix_copy_paste,
            'pixQrCode' => $this->pix_qr_code,
            'checkoutUrl' => $this->checkout_url,
            'dueAt' => $this->due_at?->toISOString(),
            'paidAt' => $this->paid_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
