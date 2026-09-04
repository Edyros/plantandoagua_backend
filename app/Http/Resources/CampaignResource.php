<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isOwner = $viewer && (int) $viewer->id === (int) $this->user_id;
        $showInviteCode = $isOwner
            && $this->visibility === Campaign::VISIBILITY_INVITE
            && in_array($this->status, [Campaign::STATUS_ACTIVE, Campaign::STATUS_PAUSED], true);

        return [
            'id' => $this->id,
            'ownerUserId' => $this->user?->uuid ?? (string) $this->user_id,
            'ownerName' => $this->whenLoaded('user', fn () => $this->user?->name),
            'name' => $this->name,
            'total' => (int) $this->total,
            'remaining' => (int) $this->remaining,
            'planted' => max(0, (int) $this->total - (int) $this->remaining),
            'visibility' => $this->visibility,
            'perUserLimit' => $this->per_user_limit,
            'status' => $this->status,
            'inviteCode' => $showInviteCode ? $this->invite_code : null,
            'paymentId' => $this->payment_id,
            'paymentStatus' => $this->when($isOwner, fn () => $this->payment?->status),
            'userPlanted' => $this->when(
                $viewer && ! $isOwner,
                fn () => $this->plantings()->where('user_id', $viewer->id)->count(),
            ),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
