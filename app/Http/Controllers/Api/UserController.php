<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlantingResource;
use App\Http\Resources\PublicUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function show(string $id): JsonResponse
    {
        $user = User::query()->where('uuid', $id)->firstOrFail();

        $plantings = $user->plantings()
            ->with('user')
            ->orderByDesc('planted_at')
            ->limit(100)
            ->get();

        if ((int) $user->trees_planted === 0 && $plantings->isNotEmpty()) {
            $user->trees_planted = (int) $plantings->sum('quantity');
        }

        return response()->json([
            'user' => new PublicUserResource($user),
            'plantings' => PlantingResource::collection($plantings),
        ]);
    }
}
