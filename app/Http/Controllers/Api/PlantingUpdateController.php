<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Planting\StorePlantingUpdateRequest;
use App\Http\Resources\PlantingUpdateResource;
use App\Models\Planting;
use App\Models\PlantingUpdate;
use App\Services\PlantingPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PlantingUpdateController extends Controller
{
    public function __construct(private PlantingPhotoService $photos) {}

    public function store(StorePlantingUpdateRequest $request, string $id): JsonResponse
    {
        $planting = Planting::query()->findOrFail($id);

        if ((int) $planting->user_id !== (int) $request->user()->id) {
            abort(403, 'Este plantio pertence a outro usuário.');
        }

        $updateId = $request->input('id') ?: (string) Str::uuid();
        $notes = $request->input('notes');
        $observedAt = $request->input('observedAt') ?? $request->input('observed_at') ?? now();
        $photoPath = $this->photos->store($request->file('photo'), $request->user()->id, 'planting-updates');

        $existing = PlantingUpdate::query()->find($updateId);
        if ($existing) {
            if ((int) $existing->user_id !== (int) $request->user()->id) {
                $this->photos->deleteMany([$photoPath]);
                abort(403, 'Esta evolução pertence a outro usuário.');
            }

            if ($existing->planting_id !== $planting->id) {
                $this->photos->deleteMany([$photoPath]);
                abort(409, 'Esta evolução já está ligada a outro plantio.');
            }

            $this->photos->deleteMany($existing->photo_uris);
            $existing->fill([
                'notes' => is_string($notes) ? $notes : $existing->notes,
                'observed_at' => $observedAt,
                'photo_uris' => [$photoPath],
            ]);
            $existing->save();
            $existing->load('user');

            return response()->json([
                'update' => new PlantingUpdateResource($existing),
            ]);
        }

        $update = PlantingUpdate::query()->create([
            'id' => $updateId,
            'planting_id' => $planting->id,
            'user_id' => $request->user()->id,
            'observed_at' => $observedAt,
            'notes' => is_string($notes) ? $notes : null,
            'photo_uris' => [$photoPath],
        ]);
        $update->load('user');

        return response()->json([
            'update' => new PlantingUpdateResource($update),
        ], 201);
    }
}
