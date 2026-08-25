<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Services\HebronPayWebhookSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HebronPayWebhookSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixtures_match_hebronpay_envelope(): void
    {
        $simulator = app(HebronPayWebhookSimulator::class);
        $names = $simulator->list();

        $this->assertContains('invoice.created', $names);
        $this->assertContains('invoice.updated.paid', $names);
        $this->assertContains('chargeback.created', $names);

        foreach ($names as $name) {
            $payload = $simulator->load($name);
            $this->assertArrayHasKey('event', $payload, $name);
            $this->assertArrayHasKey('data', $payload, $name);
            $this->assertIsString($payload['event']);
            $this->assertIsArray($payload['data']);
        }
    }

    public function test_paid_fixture_posts_to_webhook_route(): void
    {
        $payment = $this->makePayment($this->makeUser());
        $simulator = app(HebronPayWebhookSimulator::class);
        $payload = $simulator->bind($simulator->load('invoice.updated.paid'), $payment);
        $body = $simulator->signedBody($payload);

        $this->call(
            'POST',
            '/api/webhooks/hebronpay',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SIGNATURE' => $simulator->signature($body),
            ],
            $body,
        )->assertOk()->assertJsonPath('received', true);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('invoice.updated', $payment->last_webhook['event'] ?? null);
        $this->assertSame('paid', $payment->last_webhook['data']['status'] ?? null);
        $this->assertArrayHasKey('receipt', $payment->last_webhook['data']['paymentMetadata'] ?? []);
        $this->assertSame($payment->identifier, $payload['data']['externalId']);
    }

    public function test_chargeback_fixture_uses_nested_invoice(): void
    {
        $payment = $this->makePayment($this->makeUser(), [
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);

        app(HebronPayWebhookSimulator::class)->replay('chargeback.created', $payment);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PROCESSING, $payment->status);
        $this->assertSame('chargeback.created', $payment->last_webhook['event'] ?? null);
        $this->assertSame('in_dispute', $payment->last_webhook['data']['invoice']['status'] ?? null);
    }

    public function test_local_endpoint_replays_fixture_for_owner(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);
        $payment = $this->makePayment($user);

        $this->getJson('/api/dev/webhooks/hebronpay')
            ->assertOk()
            ->assertJsonFragment(['invoice.updated.paid']);

        $this->postJson("/api/dev/payments/{$payment->id}/simulate-webhook", [
            'fixture' => 'invoice.updated.expired',
        ])
            ->assertOk()
            ->assertJsonPath('received', true)
            ->assertJsonPath('fixture', 'invoice.updated.expired')
            ->assertJsonPath('payment.status', 'expired')
            ->assertJsonPath('webhook.event', 'invoice.updated')
            ->assertJsonPath('webhook.data.status', 'expired');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'expired',
        ]);
    }

    public function test_artisan_command_replays_paid_webhook(): void
    {
        $payment = $this->makePayment($this->makeUser());

        $this->artisan('hebronpay:simulate-webhook', [
            'fixture' => 'invoice.updated.paid',
            '--payment' => $payment->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePayment(User $user, array $overrides = []): Payment
    {
        return Payment::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'provider' => 'hebronpay',
            'provider_invoice_id' => 'inv_sim_1',
            'identifier' => (string) Str::uuid(),
            'amount_cents' => 150,
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Teste webhook',
            'due_at' => now()->addDay(),
        ], $overrides));
    }
}
