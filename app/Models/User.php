<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'phone',
        'cpf',
        'city',
        'state',
        'avatar_url',
        'eco_points',
        'trees_planted',
        'profile_complete',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'eco_points' => 'integer',
            'trees_planted' => 'integer',
            'profile_complete' => 'integer',
        ];
    }

    public function plantings(): HasMany
    {
        return $this->hasMany(Planting::class);
    }

    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
