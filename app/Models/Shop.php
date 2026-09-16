<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Shop extends Model
{
    public const LISTING_PRICE_CENTS = 15000;

    public const LISTING_DESCRIPTION_PREFIX = 'Anuidade da loja';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'name',
        'description',
        'phone',
        'city',
        'state',
        'latitude',
        'longitude',
        'categories',
        'products',
        'logo_url',
        'visible',
        'listing_paid_until',
        'listing_payment_id',
        'listing_settled_payment_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'visible' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'categories' => 'array',
            'products' => 'array',
            'visible' => 'boolean',
            'listing_paid_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listingPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'listing_payment_id');
    }

    public function isOwner(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function isListingActive(?Carbon $at = null): bool
    {
        if (! $this->listing_paid_until) {
            return false;
        }

        return $this->listing_paid_until->gt($at ?? now());
    }

    public function isListedPublicly(?Carbon $at = null): bool
    {
        return (bool) $this->getAttribute('visible') && $this->isListingActive($at);
    }

    public static function listingDescription(string $name): string
    {
        return self::LISTING_DESCRIPTION_PREFIX.': '.$name.' (1 ano)';
    }

    public static function activatePaid(Payment $payment): void
    {
        if ($payment->status !== Payment::STATUS_PAID) {
            return;
        }

        $shop = self::query()->where('listing_payment_id', $payment->id)->first();
        if (! $shop) {
            return;
        }

        if ($shop->listing_settled_payment_id === $payment->id) {
            return;
        }

        $from = $shop->listing_paid_until && $shop->listing_paid_until->isFuture()
            ? $shop->listing_paid_until->copy()
            : now();

        $shop->forceFill([
            'listing_paid_until' => $from->addYear(),
            'listing_settled_payment_id' => $payment->id,
        ])->save();
    }
}
