<?php

namespace Tests\Feature;

use App\Models\Planting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_or_update_preferences(): void
    {
        $this->getJson('/api/user/preferences')->assertUnauthorized();
        $this->putJson('/api/user/preferences', [
            'publicProfile' => false,
        ])->assertUnauthorized();
    }

    public function test_user_receives_default_preferences(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/user/preferences')
            ->assertOk()
            ->assertJsonPath('preferences.appearOnCommunityMap', true)
            ->assertJsonPath('preferences.publicProfile', true)
            ->assertJsonPath('preferences.showCityOnProfile', true)
            ->assertJsonPath('preferences.pinPrecision', 'exact')
            ->assertJsonPath('preferences.monthlyGoal', 20)
            ->assertJsonPath('preferences.defaultMapFilter', 'mine');

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.preferences.publicProfile', true);
    }

    public function test_user_can_update_preferences(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/user/preferences', [
            'appearOnCommunityMap' => false,
            'publicProfile' => false,
            'showCityOnProfile' => false,
            'pinPrecision' => 'approximate',
            'monthlyGoal' => 10,
            'defaultMapFilter' => 'community',
        ])
            ->assertOk()
            ->assertJsonPath('preferences.appearOnCommunityMap', false)
            ->assertJsonPath('preferences.publicProfile', false)
            ->assertJsonPath('preferences.showCityOnProfile', false)
            ->assertJsonPath('preferences.pinPrecision', 'approximate')
            ->assertJsonPath('preferences.monthlyGoal', 10)
            ->assertJsonPath('preferences.defaultMapFilter', 'community');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'appear_on_community_map' => 0,
            'public_profile' => 0,
            'show_city_on_profile' => 0,
            'pin_precision' => 'approximate',
            'monthly_goal' => 10,
            'default_map_filter' => 'community',
        ]);
    }

    public function test_community_map_hides_plantings_when_user_opts_out(): void
    {
        $viewer = $this->makeUser(['email' => 'viewer@example.com']);
        $visible = $this->makeUser(['email' => 'visible@example.com']);
        $hidden = $this->makeUser([
            'email' => 'hidden@example.com',
            'appear_on_community_map' => false,
        ]);

        $visiblePlanting = $this->makePlanting($visible, 'Ipê-amarelo');
        $this->makePlanting($hidden, 'Jequitibá');

        Sanctum::actingAs($viewer);

        $this->getJson('/api/plantings/community')
            ->assertOk()
            ->assertJsonCount(1, 'plantings')
            ->assertJsonPath('plantings.0.id', $visiblePlanting->id)
            ->assertJsonPath('plantings.0.species', 'Ipê-amarelo');

        $this->getJson('/api/plantings/'.$hidden->plantings()->first()->id)
            ->assertNotFound();

        Sanctum::actingAs($hidden);

        $this->getJson('/api/plantings/'.$hidden->plantings()->first()->id)
            ->assertOk()
            ->assertJsonPath('planting.species', 'Jequitibá');
    }

    public function test_private_profile_is_hidden_from_others_but_visible_to_owner(): void
    {
        $viewer = $this->makeUser(['email' => 'viewer@example.com']);
        $planter = $this->makeUser([
            'name' => 'Ana Souza',
            'email' => 'ana@example.com',
            'city' => 'Curitiba',
            'state' => 'PR',
            'public_profile' => false,
        ]);
        $this->makePlanting($planter, 'Ipê-amarelo');

        Sanctum::actingAs($viewer);
        $this->getJson('/api/users/'.$planter->uuid)
            ->assertNotFound()
            ->assertJsonPath('message', 'Este perfil está oculto.');

        Sanctum::actingAs($planter);
        $this->getJson('/api/users/'.$planter->uuid)
            ->assertOk()
            ->assertJsonPath('user.name', 'Ana Souza')
            ->assertJsonPath('plantings.0.species', 'Ipê-amarelo');
    }

    public function test_public_profile_omits_city_when_user_hides_it(): void
    {
        $viewer = $this->makeUser(['email' => 'viewer@example.com']);
        $planter = $this->makeUser([
            'name' => 'Ana Souza',
            'email' => 'ana@example.com',
            'city' => 'Curitiba',
            'state' => 'PR',
            'show_city_on_profile' => false,
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/users/'.$planter->uuid)
            ->assertOk()
            ->assertJsonPath('user.name', 'Ana Souza')
            ->assertJsonPath('user.city', null)
            ->assertJsonPath('user.state', null);
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

    private function makePlanting(User $user, string $species): Planting
    {
        return Planting::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'species' => $species,
            'quantity' => 1,
            'planted_at' => now(),
            'latitude' => -25.4284,
            'longitude' => -49.2733,
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);
    }
}
