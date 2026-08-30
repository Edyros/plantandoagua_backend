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
        'appear_on_community_map',
        'public_profile',
        'show_city_on_profile',
        'pin_precision',
        'monthly_goal',
        'default_map_filter',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'appear_on_community_map' => true,
        'public_profile' => true,
        'show_city_on_profile' => true,
        'pin_precision' => 'exact',
        'monthly_goal' => 20,
        'default_map_filter' => 'mine',
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
            'appear_on_community_map' => 'boolean',
            'public_profile' => 'boolean',
            'show_city_on_profile' => 'boolean',
            'monthly_goal' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function preferencePayload(): array
    {
        return [
            'appearOnCommunityMap' => (bool) $this->appear_on_community_map,
            'publicProfile' => (bool) $this->public_profile,
            'showCityOnProfile' => (bool) $this->show_city_on_profile,
            'pinPrecision' => $this->pin_precision ?: 'exact',
            'monthlyGoal' => (int) ($this->monthly_goal ?: 20),
            'defaultMapFilter' => $this->default_map_filter ?: 'mine',
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
