<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_shops(): void
    {
        $this->getJson('/api/shops')->assertUnauthorized();
    }

    public function test_user_can_create_shop_without_prices(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $id = (string) Str::uuid();

        $this->postJson('/api/shops', [
            'id' => $id,
            'name' => 'Viveiro do Vale',
            'description' => 'Mudas nativas e jardinagem',
            'phone' => '(11) 98888-7777',
            'city' => 'Campinas',
            'state' => 'sp',
            'latitude' => -22.9056,
            'longitude' => -47.0608,
            'categories' => ['mudas', 'jardinagem'],
            'products' => ['Mudas nativas', 'Adubo orgânico'],
        ])
            ->assertCreated()
            ->assertJsonPath('shop.id', $id)
            ->assertJsonPath('shop.userId', $user->uuid)
            ->assertJsonPath('shop.name', 'Viveiro do Vale')
            ->assertJsonPath('shop.state', 'SP')
            ->assertJsonPath('shop.products.0', 'Mudas nativas')
            ->assertJsonPath('shop.visible', true)
            ->assertJsonMissingPath('shop.price')
            ->assertJsonMissingPath('shop.products.0.price');

        $this->assertDatabaseHas('shops', [
            'id' => $id,
            'user_id' => $user->id,
            'name' => 'Viveiro do Vale',
        ]);
    }

    public function test_creating_same_id_updates_owned_shop(): void
    {
        $user = $this->makeUser();
        $id = (string) Str::uuid();
        Shop::query()->create($this->shopAttrs($user->id, $id, ['name' => 'Loja velha']));

        Sanctum::actingAs($user);

        $this->postJson('/api/shops', [
            'id' => $id,
            'name' => 'Loja nova',
            'latitude' => -23.55,
            'longitude' => -46.63,
            'products' => ['Ferramentas'],
        ])
            ->assertOk()
            ->assertJsonPath('shop.name', 'Loja nova')
            ->assertJsonPath('shop.products.0', 'Ferramentas');
    }

    public function test_user_cannot_create_a_second_shop(): void
    {
        $user = $this->makeUser();
        Shop::query()->create($this->shopAttrs($user->id, (string) Str::uuid()));

        Sanctum::actingAs($user);

        $this->postJson('/api/shops', [
            'id' => (string) Str::uuid(),
            'name' => 'Outra loja',
            'latitude' => -23.55,
            'longitude' => -46.63,
        ])->assertConflict();
    }

    public function test_me_returns_own_shop_or_not_found(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/shops/me')->assertNotFound();

        $shop = Shop::query()->create($this->shopAttrs($user->id, (string) Str::uuid()));

        $this->getJson('/api/shops/me')
            ->assertOk()
            ->assertJsonPath('shop.id', $shop->id);
    }

    public function test_community_lists_shops_and_others_can_view(): void
    {
        $owner = $this->makeUser(['email' => 'dona@example.com']);
        $viewer = $this->makeUser(['email' => 'visita@example.com']);
        $shop = Shop::query()->create($this->shopAttrs($owner->id, (string) Str::uuid(), [
            'name' => 'Campo Forte',
            'products' => ['Mudas de ipê'],
        ]));

        Sanctum::actingAs($viewer);

        $this->getJson('/api/shops')
            ->assertOk()
            ->assertJsonPath('shops.0.id', $shop->id)
            ->assertJsonPath('shops.0.name', 'Campo Forte');

        $this->getJson('/api/shops/'.$shop->id)
            ->assertOk()
            ->assertJsonPath('shop.userId', $owner->uuid)
            ->assertJsonMissingPath('shop.price');
    }

    public function test_hidden_shop_is_omitted_from_community_but_visible_to_owner(): void
    {
        $owner = $this->makeUser(['email' => 'dona@example.com']);
        $viewer = $this->makeUser(['email' => 'visita@example.com']);
        $shop = Shop::query()->create($this->shopAttrs($owner->id, (string) Str::uuid(), [
            'name' => 'Loja reservada',
            'visible' => false,
        ]));

        Sanctum::actingAs($viewer);

        $this->getJson('/api/shops')
            ->assertOk()
            ->assertJsonCount(0, 'shops');

        $this->getJson('/api/shops/'.$shop->id)->assertNotFound();

        Sanctum::actingAs($owner);

        $this->getJson('/api/shops')
            ->assertOk()
            ->assertJsonPath('shops.0.id', $shop->id)
            ->assertJsonPath('shops.0.visible', false);

        $this->getJson('/api/shops/'.$shop->id)
            ->assertOk()
            ->assertJsonPath('shop.visible', false);

        $this->patchJson('/api/shops/'.$shop->id, ['visible' => true])
            ->assertOk()
            ->assertJsonPath('shop.visible', true);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/shops')
            ->assertOk()
            ->assertJsonPath('shops.0.id', $shop->id);
    }

    public function test_user_cannot_update_someone_elses_shop(): void
    {
        $owner = $this->makeUser(['email' => 'dona@example.com']);
        $other = $this->makeUser(['email' => 'outra@example.com']);
        $shop = Shop::query()->create($this->shopAttrs($owner->id, (string) Str::uuid()));

        Sanctum::actingAs($other);

        $this->putJson('/api/shops/'.$shop->id, [
            'name' => 'Hijack',
        ])->assertForbidden();
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function shopAttrs(int $userId, string $id, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'user_id' => $userId,
            'name' => 'Viveiro Verde',
            'description' => null,
            'phone' => '1934567890',
            'city' => 'Piracicaba',
            'state' => 'SP',
            'latitude' => -22.7253,
            'longitude' => -47.6492,
            'categories' => ['mudas'],
            'products' => ['Mudas nativas'],
            'visible' => true,
        ], $overrides);
    }
}
