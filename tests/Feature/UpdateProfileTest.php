<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_profile(): void
    {
        $this->putJson('/api/user', [
            'name' => 'Ana Souza',
            'phone' => '11988887777',
        ])->assertUnauthorized();
    }

    public function test_user_can_update_public_and_account_fields(): void
    {
        $user = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Ana',
            'phone' => '11999999999',
            'profile_complete' => 40,
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user', [
            'name' => 'Ana Souza',
            'phone' => '(11) 98888-7777',
            'cpf' => '529.982.247-25',
            'city' => 'Curitiba',
            'state' => 'pr',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Ana Souza')
            ->assertJsonPath('user.city', 'Curitiba')
            ->assertJsonPath('user.state', 'PR')
            ->assertJsonPath('user.cpf', '529.982.247-25')
            ->assertJsonPath('user.profileComplete', 85);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);
    }

    public function test_user_can_save_cnpj_and_social_links(): void
    {
        $user = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Prefeitura Verde',
            'phone' => '11999999999',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user', [
            'name' => 'Prefeitura Verde',
            'phone' => '(11) 98888-7777',
            'cpf' => '11.444.777/0001-61',
            'website' => 'prefeituraverde.sp.gov.br',
            'instagram' => '@prefeituraverde',
            'facebook' => 'https://facebook.com/prefeituraverde',
            'linkedin' => 'https://www.linkedin.com/company/prefeituraverde',
        ])
            ->assertOk()
            ->assertJsonPath('user.cpf', '11.444.777/0001-61')
            ->assertJsonPath('user.website', 'https://prefeituraverde.sp.gov.br')
            ->assertJsonPath('user.instagram', '@prefeituraverde')
            ->assertJsonPath('user.facebook', 'https://facebook.com/prefeituraverde')
            ->assertJsonPath('user.linkedin', 'https://www.linkedin.com/company/prefeituraverde');
    }

    public function test_invalid_document_is_rejected(): void
    {
        $user = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'phone' => '11999999999',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user', [
            'name' => 'Ana Souza',
            'phone' => '(11) 98888-7777',
            'cpf' => '11.444.777/0001-00',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);
    }
}
