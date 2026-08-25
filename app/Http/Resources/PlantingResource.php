<?php

namespace App\Http\Resources;

use App\Services\PlantingPhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantingResource extends JsonResource
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
            'species' => $this->species,
            'scientificName' => $this->scientific_name,
            'quantity' => $this->quantity,
            'plantedAt' => $this->planted_at?->toISOString(),
            'supplierId' => $this->supplier_id,
            'supplierName' => $this->supplier_name,
            'observations' => $this->observations,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'locationName' => $this->location_name,
            'city' => $this->city,
            'state' => $this->state,
            'photoUris' => app(PlantingPhotoService::class)->publicUrls($this->photo_uris),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'syncStatus' => 'synced',
        ];
    }
}
