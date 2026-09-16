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

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_public_profile(): void
    {
        $planter = $this->makeUser();

        $this->getJson('/api/users/'.$planter->uuid)->assertUnauthorized();
    }

    public function test_user_can_view_another_planter_profile_without_private_fields(): void
    {
        $viewer = $this->makeUser(['email' => 'viewer@example.com']);
        $planter = $this->makeUser([
            'name' => 'Ana Souza',
            'email' => 'ana@example.com',
            'phone' => '11988887777',
            'cpf' => '529.982.247-25',
            'website' => 'https://ana.example',
            'instagram' => '@anasouza',
            'city' => 'Curitiba',
            'state' => 'PR',
            'eco_points' => 45,
            'trees_planted' => 3,
        ]);

        Planting::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $planter->id,
            'species' => 'Ipê-amarelo',
            'quantity' => 3,
            'planted_at' => now(),
            'latitude' => -25.4284,
            'longitude' => -49.2733,
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/users/'.$planter->uuid)
            ->assertOk()
            ->assertJsonPath('user.id', $planter->uuid)
            ->assertJsonPath('user.name', 'Ana Souza')
            ->assertJsonPath('user.city', 'Curitiba')
            ->assertJsonPath('user.treesPlanted', 3)
            ->assertJsonPath('user.treesAdopted', 0)
            ->assertJsonPath('user.website', 'https://ana.example')
            ->assertJsonPath('user.instagram', '@anasouza')
            ->assertJsonPath('plantings.0.species', 'Ipê-amarelo')
            ->assertJsonMissingPath('user.email')
            ->assertJsonMissingPath('user.phone')
            ->assertJsonMissingPath('user.cpf');
    }

    public function test_profile_separates_planted_and_adopted_trees(): void
    {
        $viewer = $this->makeUser(['email' => 'viewer@example.com']);
        $planter = $this->makeUser([
            'email' => 'ana@example.com',
            'trees_planted' => 0,
            'trees_adopted' => 0,
        ]);

        Planting::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $planter->id,
            'kind' => 'planted',
            'species' => 'Ipê-amarelo',
            'quantity' => 3,
            'planted_at' => now(),
            'latitude' => -25.4284,
            'longitude' => -49.2733,
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);

        Planting::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $planter->id,
            'kind' => 'adopted',
            'species' => 'Jatobá',
            'quantity' => 2,
            'planted_at' => now(),
            'latitude' => -25.4284,
            'longitude' => -49.2733,
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);

        $planter->refreshTreesCounts();

        Sanctum::actingAs($viewer);

        $this->getJson('/api/users/'.$planter->uuid)
            ->assertOk()
            ->assertJsonPath('user.treesPlanted', 3)
            ->assertJsonPath('user.treesAdopted', 2);

        Sanctum::actingAs($planter);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.treesPlanted', 3)
            ->assertJsonPath('user.treesAdopted', 2);
    }

    public function test_storing_plantings_counts_adopted_separately(): void
    {
        config(['filesystems.planting_disk' => 'public']);
        Storage::fake('public');

        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->post('/api/plantings', [
            'id' => (string) Str::uuid(),
            'species' => 'Ipê-amarelo',
            'quantity' => 4,
            'plantedAt' => now()->toISOString(),
            'latitude' => -23.55,
            'longitude' => -46.63,
            'kind' => 'planted',
            'photo' => UploadedFile::fake()->create('plantio.jpg', 80, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->post('/api/plantings', [
            'id' => (string) Str::uuid(),
            'species' => 'Jatobá',
            'quantity' => 3,
            'plantedAt' => now()->toISOString(),
            'latitude' => -23.55,
            'longitude' => -46.63,
            'kind' => 'adopted',
            'photo' => UploadedFile::fake()->create('adocao.jpg', 80, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'trees_planted' => 4,
            'trees_adopted' => 3,
        ]);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.treesPlanted', 4)
            ->assertJsonPath('user.treesAdopted', 3);
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
}
