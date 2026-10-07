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
            ->assertSee('Cada muda ajuda o mundo a melhorar.', false)
            ->assertSee('A foto, o lugar e a árvore ficam com você.', false)
            ->assertSee('A história continua.', false)
            ->assertSee('Não é só no dia do plantio.', false)
            ->assertSee('Onde cada pessoa cuidou do mundo.', false)
            ->assertSee('O ponto nasce com a muda.', false)
            ->assertSee('A foto fica com a muda.', false)
            ->assertSee('Seu pedaço de mundo começa aqui.', false)
            ->assertSee('Google Play', false)
            ->assertSee('App Store', false)
            ->assertSee(config('store.play'), false)
            ->assertSee(config('store.apple'), false)
            ->assertSee('css/index-novo.css', false)
            ->assertDontSee('Escolha a muda. Tire a foto. Pronto.', false)
            ->assertDontSee('Quero plantar', false)
            ->assertDontSee('R$', false)
            ->assertDontSee('árvores plantadas', false);
    }

    public function test_landing_hides_counts_and_prices_even_when_data_exists(): void
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

        Shop::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
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
            ->assertDontSee('árvores plantadas', false)
            ->assertDontSee('árvores adotadas', false)
            ->assertDontSee('lojas de mudas', false)
            ->assertDontSee('R$', false)
            ->assertDontSee('150', false);
    }
}
