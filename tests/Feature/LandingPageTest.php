<?php

namespace Tests\Feature;

use App\Models\Planting;
use App\Models\Shop;
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
            ->assertSee('Medalhas', false)
            ->assertSee('R$&nbsp;150', false)
            ->assertSee('lojas fantasmas', false)
            ->assertSee('R$ 150 / ano', false);
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

    public function test_landing_counts_only_shops_with_paid_listing(): void
    {
        $owner = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'phone' => '11988887777',
        ]);

        Shop::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'name' => 'Loja fantasma',
            'latitude' => -23.55,
            'longitude' => -46.63,
            'categories' => ['mudas'],
            'products' => ['Mudas'],
            'visible' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('da árvore para ler no campo', false);

        Shop::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => User::factory()->create([
                'uuid' => (string) Str::uuid(),
                'phone' => '11977776666',
                'email' => 'viveiro@example.com',
            ])->id,
            'name' => 'Viveiro do Córrego',
            'latitude' => -22.72,
            'longitude' => -47.64,
            'categories' => ['mudas'],
            'products' => ['Ipê'],
            'visible' => true,
            'listing_paid_until' => now()->addYear(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('da árvore para ler no campo', false)
            ->assertSee('lojas de mudas', false);
    }
}
