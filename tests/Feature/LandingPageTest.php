<?php

namespace Tests\Feature;

use App\Models\Planting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_the_plantando_agua_landing_page(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Plantando Água', false)
            ->assertSee('Plante hoje.', false)
            ->assertSee('Google Play', false)
            ->assertSee('App Store', false)
            ->assertSee(config('store.play'), false)
            ->assertSee('Por que Plantando Água', false)
            ->assertSee('As cinco telas do dia a dia.', false)
            ->assertSee('Medalhas', false);
    }

    public function test_landing_shows_planted_and_adopted_tree_counts_separately(): void
    {
        $user = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'phone' => '11999999999',
        ]);

        Planting::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'kind' => 'planted',
            'species' => 'Ipê-amarelo',
            'quantity' => 5,
            'planted_at' => now(),
            'latitude' => -23.55,
            'longitude' => -46.63,
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        Planting::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'kind' => 'adopted',
            'species' => 'Jatobá',
            'quantity' => 2,
            'planted_at' => now(),
            'latitude' => -23.55,
            'longitude' => -46.63,
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('árvores plantadas', false)
            ->assertSee('árvores adotadas', false)
            ->assertSee('>5</b>', false)
            ->assertSee('>2</b>', false)
            ->assertDontSee('árvores no mapa', false);
    }
}
