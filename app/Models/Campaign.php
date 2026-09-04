<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Campaign extends Model
{
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_INVITE = 'invite';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELED = 'canceled';

    public const UNIT_PRICE_CENTS = 500;

    public const DESCRIPTION_PREFIX = 'Campanha de plantio';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'name',
        'total',
        'remaining',
        'visibility',
        'invite_code',
        'status',
        'payment_id',
        'per_user_limit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'remaining' => 'integer',
            'per_user_limit' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function plantings(): HasMany
    {
        return $this->hasMany(Planting::class);
    }

    public function unlockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function isOwner(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function isInvite(): bool
    {
        return $this->visibility === self::VISIBILITY_INVITE;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->remaining > 0;
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        if ($this->visibility === self::VISIBILITY_PUBLIC && in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_PAUSED,
            self::STATUS_CLOSED,
        ], true)) {
            return true;
        }

        if ($this->isUnlockedBy($user) && in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_PAUSED,
            self::STATUS_CLOSED,
        ], true)) {
            return true;
        }

        return false;
    }

    public function canBeUsedBy(User $user): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->isOwner($user) || $this->visibility === self::VISIBILITY_PUBLIC) {
            return true;
        }

        return $this->isUnlockedBy($user);
    }

    public function isUnlockedBy(User $user): bool
    {
        if ($this->relationLoaded('unlockedUsers')) {
            return $this->unlockedUsers->contains(fn (User $item) => (int) $item->id === (int) $user->id);
        }

        return $this->unlockedUsers()->where('users.id', $user->id)->exists();
    }

    public function consumeCredit(): void
    {
        if ($this->status !== self::STATUS_ACTIVE || $this->remaining < 1) {
            abort(409, 'Esta campanha não tem mais créditos.');
        }

        $this->remaining -= 1;
        if ($this->remaining <= 0) {
            $this->remaining = 0;
            $this->status = self::STATUS_CLOSED;
        }
        $this->save();
    }

    public static function paymentDescription(string $name, int $quantity): string
    {
        $noun = $quantity === 1 ? 'registro' : 'registros';

        return self::DESCRIPTION_PREFIX.': '.$name.' ('.$quantity.' '.$noun.')';
    }

    public static function uniqueInviteCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = 'RFL-'.$suffix;
        } while (self::query()->where('invite_code', $code)->exists());

        return $code;
    }

    public static function activatePaid(Payment $payment): void
    {
        if ($payment->status !== Payment::STATUS_PAID) {
            return;
        }

        $campaign = self::query()->where('payment_id', $payment->id)->first();
        if (! $campaign || $campaign->status !== self::STATUS_PENDING_PAYMENT) {
            return;
        }

        $campaign->forceFill([
            'status' => self::STATUS_ACTIVE,
            'invite_code' => $campaign->visibility === self::VISIBILITY_INVITE
                ? ($campaign->invite_code ?: self::uniqueInviteCode())
                : null,
        ])->save();
    }

    public function activateIfPaid(): void
    {
        $payment = $this->relationLoaded('payment') ? $this->payment : $this->payment()->first();
        if ($payment) {
            self::activatePaid($payment);
        }
    }

    public static function newId(): string
    {
        return (string) Str::uuid();
    }
}
