<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Planting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'campaign_id',
        'species',
        'scientific_name',
        'quantity',
        'planted_at',
        'supplier_id',
        'supplier_name',
        'observations',
        'latitude',
        'longitude',
        'location_name',
        'city',
        'state',
        'photo_uris',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planted_at' => 'datetime',
            'quantity' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'photo_uris' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
