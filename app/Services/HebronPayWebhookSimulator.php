<?php

namespace App\Services;

use App\Models\Payment;

class HebronPayWebhookSimulator
{
    public function directory(): string
    {
        return resource_path('hebronpay/webhooks');
    }

    /**
     * @return list<string>
     */
    public function list(): array
    {
        $files = glob($this->directory().DIRECTORY_SEPARATOR.'*.json') ?: [];
        $names = array_map(
            fn (string $path): string => basename($path, '.json'),
            $files,
        );
        sort($names);

        return array_values($names);
    }

    /**
     * @return array<string, mixed>
     */
    public function load(string $fixture): array
    {
        $name = basename($fixture, '.json');
        if (! preg_match('/^[a-z0-9._-]+$/', $name)) {
            throw new \InvalidArgumentException("Fixture de webhook desconhecida: {$name}");
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$name.'.json';
        if (! is_file($path)) {
            throw new \InvalidArgumentException("Fixture de webhook desconhecida: {$name}");
        }

        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! isset($decoded['event'], $decoded['data'])) {
            throw new \RuntimeException("Fixture inválida (esperado { event, data }): {$name}");
        }

        return $decoded;
    }

    /**
     * Substitui os placeholders pelo pagamento local, no mesmo formato da HebronPay.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function bind(array $payload, Payment $payment): array
    {
        $payment->loadMissing('user');
        $now = now();

        return $this->replace($payload, [
            '__ID__' => $this->providerId($payment),
            '__IDENTIFIER__' => (string) $payment->identifier,
            '__EXTERNAL_ID__' => (string) $payment->identifier,
            '__AMOUNT__' => round($payment->amount_cents / 100, 2),
            '__DESCRIPTION__' => (string) ($payment->description ?: 'Pagamento Reflorea'),
            '__PAYMENT_METHOD__' => (string) $payment->payment_method,
            '__DUE_DATE__' => ($payment->due_at ?? $now->copy()->addDay())->toDateString(),
            '__CREATED_AT__' => ($payment->created_at ?? $now)->toJSON(),
            '__PAID_AT__' => ($payment->paid_at ?? $now)->toJSON(),
            '__CLIENT_NAME__' => (string) ($payment->user?->name ?: 'Maria Silva Santos'),
            '__CLIENT_DOCUMENT__' => preg_replace('/\D+/', '', (string) ($payment->user?->cpf ?: '52998224725')) ?: '52998224725',
            '__CLIENT_EMAIL__' => (string) ($payment->user?->email ?: 'maria.silva@email.com.br'),
            '__CLIENT_PHONE__' => preg_replace('/\D+/', '', (string) ($payment->user?->phone ?: '11999999999')) ?: '11999999999',
        ]);
    }

    /**
     * @return array{fixture: string, payload: array<string, mixed>, payment: Payment}
     */
    public function replay(string $fixture, Payment $payment): array
    {
        $payload = $this->bind($this->load($fixture), $payment);
        app(PaymentService::class)->handleWebhook($payload);

        return [
            'fixture' => basename($fixture, '.json'),
            'payload' => $payload,
            'payment' => $payment->refresh(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function signedBody(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function signature(string $body, ?string $secret = null): string
    {
        return hash_hmac('sha256', $body, $secret ?? (string) config('hebronpay.webhook_secret'));
    }

    private function providerId(Payment $payment): int|string
    {
        $id = (string) ($payment->provider_invoice_id ?: '0');

        return ctype_digit($id) ? (int) $id : $id;
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function replace(mixed $value, array $map): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = $this->replace($item, $map);
            }

            return $out;
        }

        if (! is_string($value)) {
            return $value;
        }

        if (array_key_exists($value, $map)) {
            return $map[$value];
        }

        return strtr($value, array_map(
            fn (mixed $item): string => is_scalar($item) || $item === null ? (string) $item : '',
            $map,
        ));
    }
}
