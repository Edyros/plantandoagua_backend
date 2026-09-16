<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
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
            'kind' => $this->kind,
            'name' => $this->name,
            'description' => $this->description,
            'vertices' => $this->vertices,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
