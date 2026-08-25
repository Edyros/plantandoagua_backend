<?php

namespace App\Http\Resources;

use App\Services\PlantingPhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'city' => $this->city,
            'state' => $this->state,
            'avatarUri' => app(PlantingPhotoService::class)->publicUrl($this->avatar_url),
            'ecoPoints' => (int) $this->eco_points,
            'treesPlanted' => (int) $this->trees_planted,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
