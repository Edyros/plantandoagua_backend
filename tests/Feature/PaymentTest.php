<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_payment(): void
    {
        $this->postJson('/api/payments', [
            'amount' => 10,
            'paymentMethod' => 'pix',
        ])->assertUnauthorized();
    }

    public function test_user_can_create_pix_payment(): void
    {
        Http::fake([
            'https://api.hebronpay.com.br/v1/invoices/recipient' => Http::response([
                'id' => 'inv_123',
                'status' => 'pending',
                'paymentData' => [
                    'pixKey' => '0002012635...',
                    'pixQrCode' => 'data:image/png;base64,xxx',
                ],
                'checkoutUrl' => 'https://checkout.hebronpay.com.br/pix/abc',
            ], 201),
        ]);

        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/api/payments', [
            'amount' => 12.5,
            'paymentMethod' => 'pix',
            'description' => 'Doação de muda',
        ]);

        $response->assertCreated()
            ->assertJsonPath('payment.amount', 12.5)
            ->assertJsonPath('payment.amountCents', 1250)
            ->assertJsonPath('payment.paymentMethod', 'pix')
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.pixCopyPaste', '0002012635...')
            ->assertJsonPath('payment.checkoutUrl', 'https://checkout.hebronpay.com.br/pix/abc');

        $this->assertDatabaseHas('payments', [
            'provider_invoice_id' => 'inv_123',
            'amount_cents' => 1250,
        ]);
    }

    public function test_webhook_updates_payment_status(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        Http::fake([
            'https://api.hebronpay.com.br/v1/invoices/recipient' => Http::response([
                'id' => 'inv_paid',
                'status' => 'pending',
            ], 201),
        ]);

        $created = $this->postJson('/api/payments', [
            'amount' => 5,
            'paymentMethod' => 'pix',
        ])->assertCreated();

        $payload = json_encode([
            'event' => 'invoice.paid',
            'data' => [
                'id' => 'inv_paid',
                'status' => 'paid',
                'paidAt' => '2026-08-18T12:00:00Z',
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

        $this->assertDatabaseHas('payments', [
            'id' => $created->json('payment.id'),
            'status' => 'paid',
        ]);
    }

    public function test_hebronpay_errors_are_returned_in_portuguese(): void
    {
        Http::fake([
            'https://api.hebronpay.com.br/v1/invoices/recipient' => Http::response([
                'message' => [
                    'client.O nome completo deve incluir ao menos um sobrenome.',
                    'client.Invalid CPF or CNPJ',
                    'webhookUrl must be a URL address',
                ],
                'error' => 'Bad Request',
                'statusCode' => 400,
            ], 400),
        ]);

        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/api/payments', [
            'amount' => 1,
            'paymentMethod' => 'pix',
        ]);

        $response->assertStatus(400);
        $message = $response->json('message');
        $this->assertIsString($message);
        $this->assertStringContainsString('sobrenome', $message);
        $this->assertStringContainsString('CPF ou CNPJ inválido', $message);
        $this->assertStringContainsString('webhook', mb_strtolower($message));
        $this->assertStringNotContainsStringIgnoringCase('Bad Request', $message);
        $this->assertStringNotContainsStringIgnoringCase('Invalid CPF', $message);
    }

    public function test_invalid_cpf_returns_portuguese_error(): void
    {
        Sanctum::actingAs($this->makeUser([
            'name' => 'Eduardo Silva',
            'cpf' => '121.321.233-21',
        ]));

        $this->postJson('/api/payments', [
            'amount' => 1,
            'paymentMethod' => 'pix',
        ])
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'CPF inválido. A HebronPay rejeita números sem dígitos verificadores corretos.',
            );
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
