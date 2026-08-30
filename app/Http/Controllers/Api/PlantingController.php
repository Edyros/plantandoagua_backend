<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Planting\StorePlantingRequest;
use App\Http\Requests\Planting\UpdatePlantingRequest;
use App\Http\Resources\PlantingResource;
use App\Models\Planting;
use App\Services\PlantingPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlantingController extends Controller
{
    public function __construct(private PlantingPhotoService $photos) {}

    public function index(Request $request): JsonResponse
    {
        $plantings = $request->user()
            ->plantings()
            ->with('user')
            ->orderByDesc('planted_at')
            ->limit(500)
            ->get();

        return response()->json([
            'plantings' => PlantingResource::collection($plantings),
        ]);
    }

    public function community(): JsonResponse
    {
        $plantings = Planting::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query->where('appear_on_community_map', true))
            ->orderByDesc('planted_at')
            ->limit(500)
            ->get();

        return response()->json([
            'plantings' => PlantingResource::collection($plantings),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $planting = Planting::query()->with('user')->findOrFail($id);
        $viewerId = (int) $request->user()->id;
        $isOwner = $viewerId === (int) $planting->user_id;
        $visibleOnMap = (bool) ($planting->user?->appear_on_community_map ?? true);

        if (! $isOwner && ! $visibleOnMap) {
            abort(404, 'Plantio não encontrado.');
        }

        return response()->json([
            'planting' => new PlantingResource($planting),
        ]);
    }

    public function store(StorePlantingRequest $request): JsonResponse
    {
        $data = $this->mapPayload($request->validated());
        $data['photo_uris'] = [
            $this->photos->store($request->file('photo'), $request->user()->id),
        ];
        $id = $data['id'] ?? (string) Str::uuid();

        $existing = Planting::query()->find($id);
        if ($existing) {
            if ((int) $existing->user_id !== (int) $request->user()->id) {
                abort(403, 'Este plantio pertence a outro usuário.');
            }

            $this->photos->deleteMany($existing->photo_uris);
            $existing->fill(collect($data)->except('id')->all());
            $existing->save();
            $existing->load('user');

            $this->refreshUserTreesCount($request->user());

            return response()->json([
                'planting' => new PlantingResource($existing),
            ]);
        }

        $planting = Planting::create([
            ...collect($data)->except('id')->all(),
            'id' => $id,
            'user_id' => $request->user()->id,
        ]);
        $planting->load('user');

        $this->refreshUserTreesCount($request->user());

        return response()->json([
            'planting' => new PlantingResource($planting),
        ], 201);
    }

    public function update(UpdatePlantingRequest $request, string $id): JsonResponse
    {
        $planting = Planting::query()->findOrFail($id);

        if ((int) $planting->user_id !== (int) $request->user()->id) {
            abort(403, 'Este plantio pertence a outro usuário.');
        }

        $data = $this->mapPayload($request->validated());

        if ($request->hasFile('photo')) {
            $this->photos->deleteMany($planting->photo_uris);
            $data['photo_uris'] = [
                $this->photos->store($request->file('photo'), $planting->user_id),
            ];
        }

        $planting->fill($data);
        $planting->save();
        $planting->load('user');

        $this->refreshUserTreesCount($request->user());

        return response()->json([
            'planting' => new PlantingResource($planting),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $planting = Planting::query()->findOrFail($id);

        if ((int) $planting->user_id !== (int) $request->user()->id) {
            abort(403, 'Este plantio pertence a outro usuário.');
        }

        $this->photos->deleteMany($planting->photo_uris);
        $planting->delete();
        $this->refreshUserTreesCount($request->user());

        return response()->json([
            'message' => 'Plantio removido com sucesso.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function mapPayload(array $input): array
    {
        $get = function (string $camel, string $snake) use ($input) {
            return $input[$camel] ?? $input[$snake] ?? null;
        };

        $mapped = [
            'species' => $input['species'] ?? null,
            'scientific_name' => $get('scientificName', 'scientific_name'),
            'quantity' => $input['quantity'] ?? null,
            'planted_at' => $get('plantedAt', 'planted_at'),
            'supplier_id' => $get('supplierId', 'supplier_id'),
            'supplier_name' => $get('supplierName', 'supplier_name'),
            'observations' => $input['observations'] ?? null,
            'latitude' => $input['latitude'] ?? null,
            'longitude' => $input['longitude'] ?? null,
            'location_name' => $get('locationName', 'location_name'),
            'city' => $input['city'] ?? null,
            'state' => $input['state'] ?? null,
        ];

        if (isset($input['id'])) {
            $mapped['id'] = $input['id'];
        }

        return array_filter($mapped, fn ($value) => $value !== null);
    }

    private function refreshUserTreesCount($user): void
    {
        $total = $user->plantings()->sum('quantity');
        $user->forceFill(['trees_planted' => (int) $total])->save();
    }
}
