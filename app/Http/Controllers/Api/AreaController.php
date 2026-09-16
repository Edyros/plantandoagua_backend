<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Http\Resources\AreaResource;
use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(): JsonResponse
    {
        $areas = Area::query()
            ->with('user')
            ->where('kind', Area::KIND_GREEN)
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        return response()->json([
            'areas' => AreaResource::collection($areas),
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $areas = Area::query()
            ->with('user')
            ->where('kind', Area::KIND_GREEN)
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'areas' => AreaResource::collection($areas),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $area = $this->greenAreaOrFail($id)->load('user');

        return response()->json([
            'area' => new AreaResource($area),
        ]);
    }

    public function store(StoreAreaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $id = $data['id'] ?? Area::newId();
        unset($data['id']);

        $existing = Area::query()->find($id);
        if ($existing) {
            $this->assertOwnedGreenArea($existing, $request);

            $existing->fill($this->mapPayload($data));
            $existing->save();
            $existing->load('user');

            return response()->json([
                'area' => new AreaResource($existing),
            ]);
        }

        $area = Area::query()->create([
            ...$this->mapPayload($data),
            'id' => $id,
            'user_id' => $request->user()->id,
            'kind' => Area::KIND_GREEN,
        ]);
        $area->load('user');

        return response()->json([
            'area' => new AreaResource($area),
        ], 201);
    }

    public function update(UpdateAreaRequest $request, string $id): JsonResponse
    {
        $area = $this->greenAreaOrFail($id);
        $this->assertOwnedGreenArea($area, $request);

        $area->fill($this->mapPayload($request->validated()));
        $area->save();
        $area->load('user');

        return response()->json([
            'area' => new AreaResource($area),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $area = $this->greenAreaOrFail($id);
        $this->assertOwnedGreenArea($area, $request);
        $area->delete();

        return response()->json([
            'message' => 'Área removida com sucesso.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function mapPayload(array $input): array
    {
        $mapped = [];

        if (array_key_exists('name', $input)) {
            $mapped['name'] = trim((string) $input['name']);
        }

        if (array_key_exists('description', $input)) {
            $description = trim((string) ($input['description'] ?? ''));
            $mapped['description'] = $description === '' ? null : $description;
        }

        if (isset($input['vertices']) && is_array($input['vertices'])) {
            $mapped['vertices'] = Area::normalizedVertices($input['vertices']);
        }

        return $mapped;
    }

    private function greenAreaOrFail(string $id): Area
    {
        $area = Area::query()->findOrFail($id);
        if ($area->kind !== Area::KIND_GREEN) {
            abort(404, 'Área não encontrada.');
        }

        return $area;
    }

    private function assertOwnedGreenArea(Area $area, Request $request): void
    {
        if ($area->kind !== Area::KIND_GREEN) {
            abort(404, 'Área não encontrada.');
        }

        if ((int) $area->user_id !== (int) $request->user()->id) {
            abort(403, 'Esta área pertence a outro usuário.');
        }
    }
}
