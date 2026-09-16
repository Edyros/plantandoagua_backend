<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Area extends Model
{
    public const KIND_CAMPAIGN = 'campaign';

    public const KIND_GREEN = 'green';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'kind',
        'name',
        'description',
        'vertices',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vertices' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'area_id');
    }

    /**
     * @param  list<array{latitude?: mixed, longitude?: mixed}>  $vertices
     * @return list<array{latitude: float, longitude: float}>
     */
    public static function normalizedVertices(array $vertices): array
    {
        $points = [];
        foreach ($vertices as $vertex) {
            $points[] = [
                'latitude' => (float) $vertex['latitude'],
                'longitude' => (float) $vertex['longitude'],
            ];
        }

        return $points;
    }

    /**
     * @return array{id: string, kind: string, name: mixed, vertices: mixed}
     */
    public function toMapPayload(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'name' => $this->name,
            'vertices' => $this->vertices,
        ];
    }

    public static function newId(): string
    {
        return (string) Str::uuid();
    }
}
