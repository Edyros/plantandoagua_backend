<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_areas(): void
    {
        $this->getJson('/api/areas')->assertUnauthorized();
    }

    public function test_user_can_create_green_area(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $id = (string) Str::uuid();
        $vertices = $this->square();

        $this->postJson('/api/areas', [
            'id' => $id,
            'name' => 'Parque linear do córrego',
            'description' => 'Indicação da prefeitura',
            'vertices' => $vertices,
        ])
            ->assertCreated()
            ->assertJsonPath('area.id', $id)
            ->assertJsonPath('area.userId', $user->uuid)
            ->assertJsonPath('area.userName', $user->name)
            ->assertJsonPath('area.kind', Area::KIND_GREEN)
            ->assertJsonPath('area.name', 'Parque linear do córrego')
            ->assertJsonPath('area.description', 'Indicação da prefeitura')
            ->assertJsonPath('area.vertices.0.latitude', -23.55)
            ->assertJsonPath('area.vertices.3.longitude', -46.64);

        $this->assertDatabaseHas('areas', [
            'id' => $id,
            'user_id' => $user->id,
            'kind' => Area::KIND_GREEN,
            'name' => 'Parque linear do córrego',
        ]);
    }

    public function test_creating_same_id_updates_owned_green_area(): void
    {
        $user = $this->makeUser();
        $id = (string) Str::uuid();
        Area::query()->create($this->greenAttrs($user->id, $id, ['name' => 'Área velha']));

        Sanctum::actingAs($user);

        $this->postJson('/api/areas', [
            'id' => $id,
            'name' => 'Área nova',
            'vertices' => $this->square(),
        ])
            ->assertOk()
            ->assertJsonPath('area.name', 'Área nova');
    }

    public function test_community_lists_green_areas_and_skips_campaign_areas(): void
    {
        $owner = $this->makeUser(['email' => 'orgao@example.com']);
        $viewer = $this->makeUser(['email' => 'visita@example.com']);
        $green = Area::query()->create($this->greenAttrs($owner->id, (string) Str::uuid(), [
            'name' => 'APP do rio',
        ]));
        Area::query()->create($this->greenAttrs($owner->id, (string) Str::uuid(), [
            'kind' => Area::KIND_CAMPAIGN,
            'name' => 'Campanha privada',
        ]));

        Sanctum::actingAs($viewer);

        $this->getJson('/api/areas')
            ->assertOk()
            ->assertJsonCount(1, 'areas')
            ->assertJsonPath('areas.0.id', $green->id)
            ->assertJsonPath('areas.0.name', 'APP do rio');

        $this->getJson('/api/areas/'.$green->id)
            ->assertOk()
            ->assertJsonPath('area.userId', $owner->uuid);
    }

    public function test_mine_returns_only_own_green_areas(): void
    {
        $owner = $this->makeUser(['email' => 'dona@example.com']);
        $other = $this->makeUser(['email' => 'outra@example.com']);
        $mine = Area::query()->create($this->greenAttrs($owner->id, (string) Str::uuid(), [
            'name' => 'Minha área',
        ]));
        Area::query()->create($this->greenAttrs($other->id, (string) Str::uuid(), [
            'name' => 'Área de outro',
        ]));

        Sanctum::actingAs($owner);

        $this->getJson('/api/areas/mine')
            ->assertOk()
            ->assertJsonCount(1, 'areas')
            ->assertJsonPath('areas.0.id', $mine->id);
    }

    public function test_incomplete_polygon_is_rejected(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/areas', [
            'name' => 'Área incompleta',
            'vertices' => [
                ['latitude' => -23.55, 'longitude' => -46.64],
                ['latitude' => -23.55, 'longitude' => -46.62],
            ],
        ])->assertUnprocessable();
    }

    public function test_owner_can_update_and_delete_green_area(): void
    {
        $owner = $this->makeUser();
        $area = Area::query()->create($this->greenAttrs($owner->id, (string) Str::uuid()));
        Sanctum::actingAs($owner);

        $this->putJson('/api/areas/'.$area->id, [
            'name' => 'Bosque municipal',
            'description' => 'Contato: meio ambiente',
        ])
            ->assertOk()
            ->assertJsonPath('area.name', 'Bosque municipal')
            ->assertJsonPath('area.description', 'Contato: meio ambiente');

        $this->deleteJson('/api/areas/'.$area->id)
            ->assertOk();

        $this->assertDatabaseMissing('areas', ['id' => $area->id]);
    }

    public function test_user_cannot_update_someone_elses_area(): void
    {
        $owner = $this->makeUser(['email' => 'dona@example.com']);
        $other = $this->makeUser(['email' => 'outra@example.com']);
        $area = Area::query()->create($this->greenAttrs($owner->id, (string) Str::uuid()));

        Sanctum::actingAs($other);

        $this->putJson('/api/areas/'.$area->id, [
            'name' => 'Hijack',
        ])->assertForbidden();

        $this->deleteJson('/api/areas/'.$area->id)->assertForbidden();
    }

    public function test_campaign_area_is_hidden_from_this_api(): void
    {
        $owner = $this->makeUser();
        $area = Area::query()->create($this->greenAttrs($owner->id, (string) Str::uuid(), [
            'kind' => Area::KIND_CAMPAIGN,
            'name' => 'Área da campanha',
        ]));

        Sanctum::actingAs($owner);

        $this->getJson('/api/areas/'.$area->id)->assertNotFound();
        $this->deleteJson('/api/areas/'.$area->id)->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'name' => 'Prefeitura Verde',
            'phone' => '11999999999',
        ], $overrides));
    }

    /**
     * @return list<array{latitude: float, longitude: float}>
     */
    private function square(): array
    {
        return [
            ['latitude' => -23.55, 'longitude' => -46.64],
            ['latitude' => -23.55, 'longitude' => -46.62],
            ['latitude' => -23.56, 'longitude' => -46.62],
            ['latitude' => -23.56, 'longitude' => -46.64],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function greenAttrs(int $userId, string $id, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'user_id' => $userId,
            'kind' => Area::KIND_GREEN,
            'name' => 'Área verde',
            'description' => null,
            'vertices' => $this->square(),
        ], $overrides);
    }
}
