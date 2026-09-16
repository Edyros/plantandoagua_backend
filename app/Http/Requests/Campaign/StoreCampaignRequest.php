<?php

namespace App\Http\Requests\Campaign;

use App\Models\Campaign;
use App\Support\BrazilianDocument;
use App\Support\SocialProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'website' => SocialProfile::website($this->input('website')),
            'instagram' => SocialProfile::nullable($this->input('instagram'), 120),
            'facebook' => SocialProfile::nullable($this->input('facebook')),
            'linkedin' => SocialProfile::nullable($this->input('linkedin')),
            'payerCpf' => SocialProfile::nullable($this->input('payerCpf'), 18),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:10', 'max:100000'],
            'visibility' => ['required', 'string', Rule::in([
                Campaign::VISIBILITY_PUBLIC,
                Campaign::VISIBILITY_INVITE,
            ])],
            'perUserLimit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'payerName' => ['nullable', 'string', 'max:255'],
            'payerCpf' => ['nullable', 'string', 'max:18', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value !== null && $value !== '' && ! BrazilianDocument::isValid((string) $value)) {
                    $fail('Informe um CPF ou CNPJ válido.');
                }
            }],
            'website' => ['nullable', 'string', 'max:255', 'url'],
            'instagram' => ['nullable', 'string', 'max:120'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'array'],
            'area.vertices' => ['required_with:area', 'array', 'min:4'],
            'area.vertices.*.latitude' => ['required_with:area', 'numeric', 'between:-90,90'],
            'area.vertices.*.longitude' => ['required_with:area', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da campanha.',
            'name.max' => 'O nome da campanha pode ter no máximo 80 caracteres.',
            'quantity.required' => 'Informe quantas árvores a campanha libera.',
            'quantity.min' => 'A campanha precisa liberar pelo menos 10 árvores.',
            'quantity.max' => 'A campanha pode liberar no máximo 100 mil árvores.',
            'visibility.required' => 'Escolha se a campanha é pública ou por indicação.',
            'visibility.in' => 'A campanha precisa ser pública ou por indicação.',
            'website.url' => 'Informe um site válido, como https://evento.com.br',
            'area.vertices.min' => 'Delimite a área com pelo menos 4 cantos no mapa.',
            'area.vertices.*.latitude.between' => 'A latitude da área precisa ser válida.',
            'area.vertices.*.longitude.between' => 'A longitude da área precisa ser válida.',
        ];
    }
}
