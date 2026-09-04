<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_guest_cannot_create_campaign(): void
    {
        $this->postJson('/api/campaigns', [
            'name' => 'Dia da Árvore',
            'quantity' => 10,
            'visibility' => 'public',
        ])->assertUnauthorized();
    }

    public function test_user_can_create_public_campaign_and_pay(): void
    {
        Http::fake([
            'https://api.hebronpay.com.br/v1/invoices/recipient' => Http::response([
                'id' => 'inv_campaign',
                'status' => 'pending',
                'paymentData' => [
                    'pixKey' => '0002012635...',
                    'pixQrCode' => 'data:image/png;base64,xxx',
                ],
            ], 201),
        ]);

        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/api/campaigns', [
            'name' => 'Bosque Acme',
            'quantity' => 10,
            'visibility' => 'public',
        ]);

        $response->assertCreated()
            ->assertJsonPath('campaign.name', 'Bosque Acme')
            ->assertJsonPath('campaign.total', 10)
            ->assertJsonPath('campaign.remaining', 10)
            ->assertJsonPath('campaign.visibility', 'public')
            ->assertJsonPath('campaign.status', 'pending_payment')
            ->assertJsonPath('payment.amountCents', 5000)
            ->assertJsonPath('campaign.inviteCode', null);

        $this->simulatePaidWebhook('inv_campaign');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Bosque Acme',
            'status' => Campaign::STATUS_ACTIVE,
        ]);
    }

    public function test_invite_campaign_stays_off_public_list_until_code_is_redeemed(): void
    {
        [$owner, $campaign] = $this->createPaidCampaign('invite', 2);

        Sanctum::actingAs($this->makeUser([
            'email' => 'plantador@example.com',
        ]));

        $this->getJson('/api/campaigns')
            ->assertOk()
            ->assertJsonCount(0, 'campaigns');

        $this->getJson('/api/campaigns/'.$campaign->id)
            ->assertNotFound();

        $this->postJson('/api/campaigns/redeem', [
            'code' => $campaign->fresh()->invite_code,
        ])->assertOk()
            ->assertJsonPath('campaign.id', $campaign->id)
            ->assertJsonPath('campaign.inviteCode', null);

        $this->getJson('/api/campaigns/unlocked')
            ->assertOk()
            ->assertJsonPath('campaigns.0.id', $campaign->id);

        Sanctum::actingAs($owner);
        $this->getJson('/api/campaigns/mine')
            ->assertOk()
            ->assertJsonPath('campaigns.0.inviteCode', $campaign->fresh()->invite_code);
    }

    public function test_planting_consumes_campaign_credit_and_rejects_when_empty(): void
    {
        [$owner, $campaign] = $this->createPaidCampaign('public', 10);
        $campaign->forceFill(['remaining' => 1])->save();
        $planter = $this->makeUser(['email' => 'maria@example.com']);
        Sanctum::actingAs($planter);

        $this->post('/api/plantings', $this->plantingPayload([
            'campaignId' => $campaign->id,
        ]), ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('planting.campaignId', $campaign->id)
            ->assertJsonPath('planting.campaignName', $campaign->name);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'remaining' => 0,
            'status' => Campaign::STATUS_CLOSED,
        ]);

        $this->post('/api/plantings', $this->plantingPayload([
            'id' => (string) Str::uuid(),
            'campaignId' => $campaign->id,
        ]), ['Accept' => 'application/json'])->assertStatus(409);

        Sanctum::actingAs($planter);
        $campaign->refresh();
        $this->assertTrue($campaign->canBeViewedBy($planter), $campaign->status.' '.$campaign->visibility);
        $this->getJson('/api/campaigns/'.$campaign->id.'/plantings')
            ->assertOk()
            ->assertJsonCount(1, 'plantings');

        Sanctum::actingAs($owner);
        $this->getJson('/api/campaigns/'.$campaign->id.'/plantings')
            ->assertOk()
            ->assertJsonCount(1, 'plantings');
    }

    public function test_owner_can_pause_resume_and_cancel_campaign(): void
    {
        [$owner, $campaign] = $this->createPaidCampaign('public', 2);
        $planter = $this->makeUser(['email' => 'pausa@example.com']);

        Sanctum::actingAs($owner);
        $this->getJson('/api/campaigns/'.$campaign->id)
            ->assertOk()
            ->assertJsonPath('campaign.status', 'active')
            ->assertJsonPath('campaign.paymentStatus', 'paid')
            ->assertJsonPath('payment.status', 'paid');

        $this->postJson('/api/campaigns/'.$campaign->id.'/status', [
            'status' => 'paused',
        ])->assertOk()
            ->assertJsonPath('campaign.status', 'paused')
            ->assertJsonPath('campaign.paymentStatus', 'paid');

        Sanctum::actingAs($planter);
        $this->post('/api/plantings', $this->plantingPayload([
            'campaignId' => $campaign->id,
        ]), ['Accept' => 'application/json'])->assertStatus(409);

        Sanctum::actingAs($owner);
        $this->postJson('/api/campaigns/'.$campaign->id.'/status', [
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('campaign.status', 'active');

        $this->postJson('/api/campaigns/'.$campaign->id.'/status', [
            'status' => 'canceled',
        ])->assertOk()
            ->assertJsonPath('campaign.status', 'canceled');

        Sanctum::actingAs($planter);
        $this->post('/api/plantings', $this->plantingPayload([
            'id' => (string) Str::uuid(),
            'campaignId' => $campaign->id,
        ]), ['Accept' => 'application/json'])->assertStatus(409);
    }

    public function test_paid_webhook_does_not_activate_canceled_campaign(): void
    {
        Http::fake([
            'https://api.hebronpay.com.br/v1/invoices/recipient' => Http::response([
                'id' => 'inv_cancel_pending',
                'status' => 'pending',
                'paymentData' => [
                    'pixKey' => '0002012635...',
                ],
            ], 201),
            'https://api.hebronpay.com.br/v1/invoices/recipient/*' => Http::response([
                'status' => 'canceled',
            ], 200),
        ]);

        Sanctum::actingAs($this->makeUser());

        $created = $this->postJson('/api/campaigns', [
            'name' => 'Viva bem',
            'quantity' => 10,
            'visibility' => 'public',
        ])->assertCreated();

        $campaignId = $created->json('campaign.id');

        $this->postJson('/api/campaigns/'.$campaignId.'/status', [
            'status' => 'canceled',
        ])->assertOk()
            ->assertJsonPath('campaign.status', 'canceled')
            ->assertJsonPath('campaign.paymentStatus', 'canceled');

        $this->simulatePaidWebhook('inv_cancel_pending');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaignId,
            'status' => Campaign::STATUS_CANCELED,
        ]);
    }

    public function test_opening_campaign_activates_it_when_pix_is_already_paid(): void
    {
        Http::fake([
            'https://api.hebronpay.com.br/v1/invoices/recipient' => Http::response([
                'id' => 'inv_stuck',
                'status' => 'pending',
            ], 201),
            'https://api.hebronpay.com.br/v1/invoices/recipient/*' => Http::response([
                'id' => 'inv_stuck',
                'status' => 'paid',
                'paidAt' => '2026-09-02T12:00:00Z',
            ], 200),
        ]);

        Sanctum::actingAs($this->makeUser());

        $created = $this->postJson('/api/campaigns', [
            'name' => 'Viva bem',
            'quantity' => 10,
            'visibility' => 'public',
        ])->assertCreated()
            ->assertJsonPath('campaign.status', 'pending_payment');

        $campaign = Campaign::query()->findOrFail($created->json('campaign.id'));
        $campaign->payment->forceFill(['status' => Payment::STATUS_PAID])->save();

        $this->getJson('/api/campaigns/'.$campaign->id)
            ->assertOk()
            ->assertJsonPath('campaign.status', 'active')
            ->assertJsonPath('campaign.paymentStatus', 'paid')
            ->assertJsonPath('payment.status', 'paid');
    }

    public function test_opening_campaign_activates_it_when_provider_already_received_pix(): void
    {
        Http::fake([
            'https://api.hebronpay.com.br/v1/invoices/recipient' => Http::response([
                'id' => 'inv_refresh_paid',
                'status' => 'pending',
            ], 201),
            'https://api.hebronpay.com.br/v1/invoices/recipient/*' => Http::response([
                'id' => 'inv_refresh_paid',
                'status' => 'paid',
                'paidAt' => '2026-09-02T12:00:00Z',
            ], 200),
        ]);

        Sanctum::actingAs($this->makeUser());

        $created = $this->postJson('/api/campaigns', [
            'name' => 'Bosque pago',
            'quantity' => 10,
            'visibility' => 'public',
        ])->assertCreated()
            ->assertJsonPath('campaign.status', 'pending_payment');

        $this->getJson('/api/campaigns/'.$created->json('campaign.id'))
            ->assertOk()
            ->assertJsonPath('campaign.status', 'active')
            ->assertJsonPath('campaign.paymentStatus', 'paid');
    }

    public function test_per_user_limit_enforced(): void
    {
        [$owner, $campaign] = $this->createPaidCampaign('public', 5, 1);

        $this->assertSame(1, $campaign->per_user_limit);

        $planter = $this->makeUser(['email' => 'limite@example.com']);
        Sanctum::actingAs($planter);

        $this->post('/api/plantings', $this->plantingPayload([
            'campaignId' => $campaign->id,
        ]), ['Accept' => 'application/json'])->assertCreated();

        $this->post('/api/plantings', $this->plantingPayload([
            'id' => (string) Str::uuid(),
            'campaignId' => $campaign->id,
        ]), ['Accept' => 'application/json'])->assertStatus(409);

        Sanctum::actingAs($planter);
        $this->getJson('/api/campaigns/'.$campaign->id)
            ->assertOk()
            ->assertJsonPath('campaign.perUserLimit', 1)
            ->assertJsonPath('campaign.userPlanted', 1);
    }

    public function test_per_user_limit_null_means_unlimited(): void
    {
        [$owner, $campaign] = $this->createPaidCampaign('public', 3);

        $this->assertNull($campaign->per_user_limit);

        $planter = $this->makeUser(['email' => 'nolimit@example.com']);
        Sanctum::actingAs($planter);

        $this->post('/api/plantings', $this->plantingPayload([
            'campaignId' => $campaign->id,
        ]), ['Accept' => 'application/json'])->assertCreated();

        $this->post('/api/plantings', $this->plantingPayload([
            'id' => (string) Str::uuid(),
            'campaignId' => $campaign->id,
        ]), ['Accept' => 'application/json'])->assertCreated();

        $this->getJson('/api/campaigns/'.$campaign->id)
            ->assertOk()
            ->assertJsonPath('campaign.perUserLimit', null);
    }

    /**
     * @return array{0: User, 1: Campaign}
     */
    private function createPaidCampaign(string $visibility, int $quantity, ?int $perUserLimit = null): array
    {
        Http::fake([
            'https://api.hebronpay.com.br/v1/invoices/recipient' => Http::response([
                'id' => 'inv_'.$visibility,
                'status' => 'pending',
            ], 201),
        ]);

        $owner = $this->makeUser();
        Sanctum::actingAs($owner);

        $payload = [
            'name' => 'Campanha '.$visibility,
            'quantity' => max(10, $quantity),
            'visibility' => $visibility,
        ];
        if ($perUserLimit !== null) {
            $payload['perUserLimit'] = $perUserLimit;
        }

        $created = $this->postJson('/api/campaigns', $payload)->assertCreated();

        $this->simulatePaidWebhook('inv_'.$visibility);

        $campaign = Campaign::query()->findOrFail($created->json('campaign.id'));

        return [$owner, $campaign];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function plantingPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid(),
            'species' => 'Ipê-amarelo',
            'quantity' => 1,
            'plantedAt' => now()->toISOString(),
            'latitude' => -23.55,
            'longitude' => -46.63,
            'photo' => UploadedFile::fake()->create('plantio.jpg', 80, 'image/jpeg'),
        ], $overrides);
    }

    private function simulatePaidWebhook(string $invoiceId): void
    {
        $payload = json_encode([
            'event' => 'invoice.paid',
            'data' => [
                'id' => $invoiceId,
                'status' => 'paid',
                'paidAt' => '2026-09-02T12:00:00Z',
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $payload, 'test-webhook-secret');

        $this->call(
            'POST',
            '/api/webhooks/hebronpay',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SIGNATURE' => $signature,
            ],
            $payload,
        )->assertOk();
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
            'cpf' => '529.982.247-25',
        ], $overrides));
    }
}
