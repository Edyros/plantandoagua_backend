<?php

namespace App\Services;

use App\Exceptions\HebronPayException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HebronPayClient
{
    public function createInvoice(array $payload): array
    {
        return $this->request('post', '/invoices/recipient', $payload);
    }

    public function getInvoice(string $id): array
    {
        return $this->request('get', '/invoices/recipient/'.$id);
    }

    public function cancelInvoice(string $id): array
    {
        return $this->request('delete', '/invoices/recipient/'.$id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        $key = (string) config('hebronpay.api_key');
        $secret = (string) config('hebronpay.api_secret');

        if ($key === '' || $secret === '') {
            throw new HebronPayException(
                'Credenciais da HebronPay não configuradas no servidor.',
                503,
            );
        }

        $url = config('hebronpay.base_url').$path;

        $pending = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('hebronpay.timeout', 30))
            ->withHeaders([
                'x-api-key' => $key,
                'x-api-secret' => $secret,
            ]);

        /** @var Response $response */
        $response = match ($method) {
            'get' => $pending->get($url, $payload),
            'delete' => $pending->delete($url),
            default => $pending->{$method}($url, $payload),
        };

        $body = $response->json();
        if (! is_array($body)) {
            $body = ['raw' => $response->body()];
        }

        if ($response->failed()) {
            $message = $this->errorMessage($body) ?? 'Falha ao comunicar com o provedor de pagamento.';
            Log::warning('HebronPay request failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $body,
            ]);

            throw new HebronPayException($message, $response->status() >= 500 ? 502 : $response->status(), $body);
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function errorMessage(array $body): ?string
    {
        $specific = [];
        $fallback = null;

        foreach (['message', 'error', 'detail'] as $key) {
            foreach ($this->flattenErrorValue($body[$key] ?? null) as $item) {
                $translated = $this->translateError($item);
                if ($translated === '') {
                    continue;
                }

                if ($this->isGenericHttpReason($item)) {
                    $fallback ??= $translated;
                    continue;
                }

                $specific[] = $translated;
            }
        }

        $specific = array_values(array_unique($specific));

        if ($specific !== []) {
            return implode("\n", $specific);
        }

        return $fallback;
    }

    /**
     * @return list<string>
     */
    private function flattenErrorValue(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $parts = [];
        foreach ($value as $item) {
            $parts = [...$parts, ...$this->flattenErrorValue($item)];
        }

        return $parts;
    }

    private function translateError(string $message): string
    {
        $normalized = trim(preg_replace('/^client\./i', '', $message) ?? $message);
        $key = mb_strtolower($normalized);

        foreach ($this->errorTranslations() as $english => $portuguese) {
            if ($key === $english || str_contains($key, $english)) {
                return $portuguese;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    private function errorTranslations(): array
    {
        return [
            'invalid cpf or cnpj' => 'CPF ou CNPJ inválido.',
            'webhookurl must be a url address' => 'A URL de webhook é inválida. Em localhost ela não é enviada.',
            'must be a url address' => 'Informe uma URL válida.',
            'bad request' => 'Não foi possível criar o pagamento. Verifique nome completo e CPF.',
            'unauthorized' => 'A HebronPay recusou as credenciais do servidor.',
            'forbidden' => 'A HebronPay negou o acesso a esta operação.',
            'not found' => 'Pagamento não encontrado na HebronPay.',
            'unprocessable entity' => 'A HebronPay recusou os dados enviados.',
            'internal server error' => 'A HebronPay teve uma falha interna. Tente de novo em instantes.',
            'too many requests' => 'Muitas tentativas na HebronPay. Aguarde um momento.',
        ];
    }

    private function isGenericHttpReason(string $message): bool
    {
        $key = mb_strtolower($message);

        return in_array($key, [
            'bad request',
            'unauthorized',
            'forbidden',
            'error',
        ], true);
    }
}
