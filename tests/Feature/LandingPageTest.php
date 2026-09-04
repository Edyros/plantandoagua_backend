<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
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
}
