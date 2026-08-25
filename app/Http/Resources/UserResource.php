<?php

namespace App\Http\Resources;

use App\Services\PlantingPhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?? '',
            'cpf' => $this->cpf,
            'city' => $this->city,
            'state' => $this->state,
            'avatarUri' => app(PlantingPhotoService::class)->publicUrl($this->avatar_url),
            'ecoPoints' => $this->eco_points,
            'treesPlanted' => $this->trees_planted,
            'profileComplete' => $this->profile_complete,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'syncStatus' => 'synced',
        ];
    }
}
