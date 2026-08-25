<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHOD_PIX = 'pix';
    public const METHOD_CREDIT_CARD = 'credit_card';
    public const METHOD_BANK_SLIP = 'bank_slip';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PROCESSING = 'processing';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'provider',
        'provider_invoice_id',
        'identifier',
        'amount_cents',
        'currency',
        'payment_method',
        'status',
        'description',
        'pix_copy_paste',
        'pix_qr_code',
        'checkout_url',
        'due_at',
        'paid_at',
        'provider_payload',
        'last_webhook',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'provider_payload' => 'array',
            'last_webhook' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            'pending_generation',
            'pending_antifraud_auth',
            'partially_paid',
        ], true);
    }
}
