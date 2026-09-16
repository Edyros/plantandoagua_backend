<?php

namespace App\Http\Requests\Shop;

use App\Support\BrazilianDocument;
use App\Support\SocialProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreShopListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payerCpf' => SocialProfile::nullable($this->input('payerCpf'), 18),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payerName' => ['nullable', 'string', 'max:255'],
            'payerCpf' => ['nullable', 'string', 'max:18', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value !== null && $value !== '' && ! BrazilianDocument::isValid((string) $value)) {
                    $fail('Informe um CPF ou CNPJ válido.');
                }
            }],
        ];
    }
}
