<?php

namespace App\Http\Resources;

use App\Services\PlantingPhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantingUpdateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $owner = $this->relationLoaded('user') ? $this->user : null;

        return [
            'id' => $this->id,
            'plantingId' => $this->planting_id,
            'userId' => $owner?->uuid ?? (string) $this->user_id,
            'observedAt' => $this->observed_at?->toISOString(),
            'notes' => $this->notes,
            'photoUris' => app(PlantingPhotoService::class)->publicUrls($this->photo_uris),
            'createdAt' => $this->created_at?->toISOString(),
            'syncStatus' => 'synced',
        ];
    }
}
