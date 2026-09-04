<?php

namespace App\Http\Resources;

use App\Services\PlantingPhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user?->uuid ?? (string) $this->user_id,
            'userName' => $this->whenLoaded('user', fn () => $this->user?->name),
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'city' => $this->city,
            'state' => $this->state,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'categories' => array_values($this->categories ?? []),
            'products' => array_values($this->products ?? []),
            'logoUri' => app(PlantingPhotoService::class)->publicUrl($this->logo_url),
            'visible' => (bool) ($this->visible ?? true),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'syncStatus' => 'synced',
        ];
    }
}
