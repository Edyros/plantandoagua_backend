<?php

namespace Tests\Feature;

use App\Models\Planting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertJsonPath('plantings.0.species', 'Ipê-amarelo')
            ->assertJsonMissingPath('user.email')
            ->assertJsonMissingPath('user.phone')
            ->assertJsonMissingPath('user.cpf');
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
