<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shop extends Model
{
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
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
