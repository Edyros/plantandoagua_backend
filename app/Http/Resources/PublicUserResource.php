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
        $showCity = (bool) $this->show_city_on_profile;

        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'city' => $showCity ? $this->city : null,
            'state' => $showCity ? $this->state : null,
            'avatarUri' => app(PlantingPhotoService::class)->publicUrl($this->avatar_url),
            'ecoPoints' => (int) $this->eco_points,
            'treesPlanted' => (int) $this->trees_planted,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
