<?php

namespace Tests\Feature;

use App\Models\Planting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlantingUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.planting_disk' => 'public']);
        Storage::fake('public');
    }

    public function test_guest_cannot_create_update(): void
    {
        $owner = $this->makeUser();
        $planting = $this->makePlanting($owner);

        $this->post('/api/plantings/'.$planting->id.'/updates', [
            'photo' => UploadedFile::fake()->create('evolucao.jpg', 80, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertUnauthorized();
    }

    public function test_owner_can_register_evolution_with_photo_and_comment(): void
    {
        $owner = $this->makeUser();
        $planting = $this->makePlanting($owner);
        $updateId = (string) Str::uuid();

        Sanctum::actingAs($owner);

        $this->post('/api/plantings/'.$planting->id.'/updates', [
            'id' => $updateId,
            'notes' => 'A muda já soltou folhas novas.',
            'photo' => UploadedFile::fake()->create('evolucao.jpg', 80, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('update.id', $updateId)
            ->assertJsonPath('update.plantingId', $planting->id)
            ->assertJsonPath('update.userId', $owner->uuid)
            ->assertJsonPath('update.notes', 'A muda já soltou folhas novas.')
            ->assertJsonPath('update.syncStatus', 'synced');

        $this->assertDatabaseHas('planting_updates', [
            'id' => $updateId,
            'planting_id' => $planting->id,
            'user_id' => $owner->id,
            'notes' => 'A muda já soltou folhas novas.',
        ]);
    }

    public function test_photo_is_required(): void
    {
        $owner = $this->makeUser();
        $planting = $this->makePlanting($owner);

        Sanctum::actingAs($owner);

        $this->postJson('/api/plantings/'.$planting->id.'/updates', [
            'notes' => 'Sem foto',
        ])->assertUnprocessable();
    }

    public function test_other_user_cannot_add_evolution(): void
    {
        $owner = $this->makeUser(['email' => 'dona@example.com']);
        $visitor = $this->makeUser(['email' => 'visita@example.com']);
        $planting = $this->makePlanting($owner);

        Sanctum::actingAs($visitor);

        $this->post('/api/plantings/'.$planting->id.'/updates', [
            'photo' => UploadedFile::fake()->create('evolucao.jpg', 80, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_same_id_is_idempotent(): void
    {
        $owner = $this->makeUser();
        $planting = $this->makePlanting($owner);
        $updateId = (string) Str::uuid();

        Sanctum::actingAs($owner);

        $this->post('/api/plantings/'.$planting->id.'/updates', [
            'id' => $updateId,
            'notes' => 'Primeira visita',
            'photo' => UploadedFile::fake()->create('evolucao-1.jpg', 80, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->post('/api/plantings/'.$planting->id.'/updates', [
            'id' => $updateId,
            'notes' => 'Primeira visita (retry)',
            'photo' => UploadedFile::fake()->create('evolucao-2.jpg', 80, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('update.id', $updateId)
            ->assertJsonPath('update.notes', 'Primeira visita (retry)');

        $this->assertDatabaseCount('planting_updates', 1);
    }

    public function test_show_includes_updates_and_list_does_not(): void
    {
        $owner = $this->makeUser();
        $planting = $this->makePlanting($owner);

        Sanctum::actingAs($owner);

        $this->post('/api/plantings/'.$planting->id.'/updates', [
            'notes' => 'Cresceu bem.',
            'photo' => UploadedFile::fake()->create('evolucao.jpg', 80, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->getJson('/api/plantings/'.$planting->id)
            ->assertOk()
            ->assertJsonPath('planting.updates.0.notes', 'Cresceu bem.')
            ->assertJsonPath('planting.updates.0.plantingId', $planting->id);

        $this->getJson('/api/plantings')
            ->assertOk()
            ->assertJsonMissingPath('plantings.0.updates');
    }

    public function test_community_can_see_evolution_of_visible_planting(): void
    {
        $owner = $this->makeUser(['email' => 'dona@example.com']);
        $visitor = $this->makeUser(['email' => 'visita@example.com']);
        $planting = $this->makePlanting($owner);

        Sanctum::actingAs($owner);
        $this->post('/api/plantings/'.$planting->id.'/updates', [
            'notes' => 'Visita da primavera',
            'photo' => UploadedFile::fake()->create('evolucao.jpg', 80, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        Sanctum::actingAs($visitor);
        $this->getJson('/api/plantings/'.$planting->id)
            ->assertOk()
            ->assertJsonPath('planting.updates.0.notes', 'Visita da primavera');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'name' => 'Mariana Silva',
            'phone' => '11999999999',
        ], $overrides));
    }

    private function makePlanting(User $user): Planting
    {
        return Planting::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'species' => 'Ipê-amarelo',
            'quantity' => 1,
            'planted_at' => now(),
            'latitude' => -25.4284,
            'longitude' => -49.2733,
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);
    }
}
