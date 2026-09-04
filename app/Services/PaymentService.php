<?php

namespace App\Services;

use App\Exceptions\HebronPayException;
use App\Models\Campaign;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private HebronPayClient $hebronPay) {}

    /**
     * @param  array{amount: float|int, paymentMethod: string, description?: string, dueDate?: string, payerName?: string, payerCpf?: string}  $input
     */
    public function create(User $user, array $input): Payment
    {
        $this->payerName($user, $input);
        $this->payerDocument($user, $input);

        $amountCents = $this->toCents($input['amount']);
        $method = $input['paymentMethod'];
        $identifier = (string) Str::uuid();
        $dueAt = isset($input['dueDate'])
            ? Carbon::parse($input['dueDate'])
            : now()->addDay();

        $payment = Payment::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'provider' => 'hebronpay',
            'identifier' => $identifier,
            'amount_cents' => $amountCents,
            'currency' => 'BRL',
            'payment_method' => $method,
            'status' => Payment::STATUS_PENDING,
            'description' => $input['description'] ?? 'Pagamento Reflorea',
            'due_at' => $dueAt,
        ]);

        try {
            $remote = $this->hebronPay->createInvoice($this->invoicePayload($user, $payment, $input));
            $this->applyRemote($payment, $remote);
        } catch (HebronPayException $e) {
            $payment->forceFill([
                'status' => Payment::STATUS_FAILED,
                'provider_payload' => $e->body ?: ['error' => $e->getMessage()],
            ])->save();

            throw $e;
        }

        return $payment->fresh() ?? $payment;
    }

    public function showForUser(User $user, string $id): Payment
    {
        /** @var Payment $payment */
        $payment = Payment::query()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        if ($payment->isPending() && $payment->provider_invoice_id) {
            $this->refreshFromProvider($payment);
        }

        return $payment->fresh() ?? $payment;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Payment>
     */
    public function listForUser(User $user)
    {
        return $user->payments()->orderByDesc('created_at')->limit(100)->get();
    }

    public function cancelForUser(User $user, string $id): Payment
    {
        /** @var Payment $payment */
        $payment = Payment::query()
            ->where('user_id', $user->id)
            ->findOrFail($id);

        if (! $payment->isPending()) {
            throw new HebronPayException('Este pagamento não pode mais ser cancelado.', 422);
        }

        if ($payment->provider_invoice_id) {
            $remote = $this->hebronPay->cancelInvoice($payment->provider_invoice_id);
            $this->applyRemote($payment, $remote);
        }

        if ($payment->isPending()) {
            $payment->forceFill(['status' => Payment::STATUS_CANCELED])->save();
        }

        return $payment->fresh() ?? $payment;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void
    {
        $event = strtolower((string) ($payload['event'] ?? ''));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $invoice = $data;

        if (str_starts_with($event, 'chargeback.') && is_array($data['invoice'] ?? null)) {
            $invoice = $data['invoice'];
        }

        $payment = $this->findPaymentFromRemote($invoice);

        if (! $payment) {
            return;
        }

        $this->applyRemote($payment, $invoice);
        $payment->forceFill(['last_webhook' => $payload])->save();
    }

    public function refreshFromProvider(Payment $payment): Payment
    {
        if (! $payment->provider_invoice_id) {
            return $payment;
        }

        try {
            $remote = $this->hebronPay->getInvoice($payment->provider_invoice_id);
            $this->applyRemote($payment, $remote);
        } catch (HebronPayException) {
            // Mantém o registro local se a consulta remota falhar.
        }

        return $payment->fresh() ?? $payment;
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function applyRemote(Payment $payment, array $remote): void
    {
        $invoice = is_array($remote['invoice'] ?? null) ? $remote['invoice'] : $remote;
        $pix = $this->nested($invoice, ['paymentData', 'pix', 'payment', 'payment.pix']);

        $payment->forceFill([
            'provider_invoice_id' => $this->firstString($invoice, ['id', 'invoiceId', 'transactionId', '_id'])
                ?? $payment->provider_invoice_id,
            'status' => $this->mapStatus($this->firstString($invoice, ['status', 'invoiceStatus'])),
            'pix_copy_paste' => $this->firstString($pix + $invoice, [
                'pixKey',
                'emv',
                'emvCode',
                'pixCopyPaste',
                'copyPaste',
                'qrcodeText',
                'pix_code',
                'pixCode',
                'brCode',
            ]) ?? $payment->pix_copy_paste,
            'pix_qr_code' => $this->firstString($pix + $invoice, [
                'pixQrCode',
                'qrCode',
                'qr_code',
                'qrcode',
                'qrCodeUrl',
                'qrCodeBase64',
            ]) ?? $payment->pix_qr_code,
            'checkout_url' => $this->firstString($invoice, [
                'checkoutUrl',
                'publicUrl',
                'url',
                'paymentUrl',
            ]) ?? $payment->checkout_url,
            'paid_at' => $this->parseDate($this->firstString($invoice, ['paidAt', 'paid_at'])) ?? $payment->paid_at,
            'provider_payload' => $invoice,
        ])->save();

        Campaign::activatePaid($payment->fresh() ?? $payment);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findPaymentFromRemote(array $data): ?Payment
    {
        $invoice = is_array($data['invoice'] ?? null) ? $data['invoice'] : $data;
        $providerId = $this->firstString($invoice, ['id', 'invoiceId', 'transactionId', '_id']);
        $identifier = $this->firstString($invoice, ['identifier', 'externalId', 'externalRef', 'referenceId']);

        if ($providerId) {
            $found = Payment::query()->where('provider_invoice_id', $providerId)->first();
            if ($found) {
                return $found;
            }
        }

        if ($identifier) {
            return Payment::query()->where('identifier', $identifier)->first();
        }

        return null;
    }

    /**
     * @param  array{payerName?: string, payerCpf?: string}  $input
     * @return array<string, mixed>
     */
    private function invoicePayload(User $user, Payment $payment, array $input = []): array
    {
        $payload = [
            'amount' => round($payment->amount_cents / 100, 2),
            'paymentMethod' => $payment->payment_method,
            'externalId' => $payment->identifier,
            'dueDate' => $payment->due_at?->toDateString(),
            'metadata' => [
                'source' => 'reflorea',
                'description' => $payment->description,
                'paymentContext' => [
                    'operationId' => $payment->identifier,
                ],
            ],
            'client' => array_filter([
                'name' => $this->payerName($user, $input),
                'document' => $this->payerDocument($user, $input),
                'email' => $user->email,
                'phone' => preg_replace('/\D+/', '', (string) $user->phone) ?: null,
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        $webhookUrl = $this->publicWebhookUrl();
        if ($webhookUrl !== null) {
            $payload['webhookUrl'] = $webhookUrl;
        }

        return $payload;
    }

    /**
     * @param  array{payerName?: string}  $input
     */
    private function payerName(User $user, array $input): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) ($input['payerName'] ?? $user->name)) ?? '');
        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($parts) || count($parts) < 2) {
            throw new HebronPayException(
                'A HebronPay exige nome e sobrenome. Atualize o cadastro ou informe o nome completo no teste.',
                422,
            );
        }

        return $name;
    }

    /**
     * @param  array{payerCpf?: string}  $input
     */
    private function payerDocument(User $user, array $input): string
    {
        $document = preg_replace('/\D+/', '', (string) ($input['payerCpf'] ?? $user->cpf)) ?: '';

        if (strlen($document) === 14) {
            throw new HebronPayException('Use um CPF de 11 dígitos no pagador.', 422);
        }

        if (strlen($document) !== 11 || ! $this->isValidCpf($document)) {
            throw new HebronPayException(
                'CPF inválido. A HebronPay rejeita números sem dígitos verificadores corretos.',
                422,
            );
        }

        return $document;
    }

    private function isValidCpf(string $digits): bool
    {
        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;
            for ($i = 0; $i < $position; $i++) {
                $sum += (int) $digits[$i] * (($position + 1) - $i);
            }
            $check = ((10 * $sum) % 11) % 10;
            if ((int) $digits[$position] !== $check) {
                return false;
            }
        }

        return true;
    }

    private function publicWebhookUrl(): ?string
    {
        $configured = trim((string) config('hebronpay.webhook_url', ''));
        $url = $configured !== ''
            ? $configured
            : rtrim((string) config('app.url'), '/').'/api/webhooks/hebronpay';

        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';

        if (! in_array($scheme, ['http', 'https'], true) || ! is_string($host) || $host === '') {
            return null;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return null;
        }

        if (! str_contains($host, '.')) {
            return null;
        }

        return $url;
    }

    private function toCents(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function mapStatus(?string $status): string
    {
        $normalized = strtolower((string) $status);

        return match ($normalized) {
            'paid', 'approved', 'completed', 'success' => Payment::STATUS_PAID,
            'canceled', 'cancelled', 'rejected' => Payment::STATUS_CANCELED,
            'in_dispute' => Payment::STATUS_PROCESSING,
            'expired' => Payment::STATUS_EXPIRED,
            'failed', 'error' => Payment::STATUS_FAILED,
            'refunded', 'pending_refund' => Payment::STATUS_REFUNDED,
            'processing', 'pending_generation', 'pending_antifraud_auth', 'in_dispute', 'partially_paid' => Payment::STATUS_PROCESSING,
            default => Payment::STATUS_PENDING,
        };
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $keys
     */
    private function firstString(array $source, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $source[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $paths
     * @return array<string, mixed>
     */
    private function nested(array $source, array $paths): array
    {
        foreach ($paths as $path) {
            $current = $source;
            foreach (explode('.', $path) as $segment) {
                if (! is_array($current[$segment] ?? null)) {
                    $current = [];
                    break;
                }
                $current = $current[$segment];
            }
            if ($current !== []) {
                return $current;
            }
        }

        return [];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
