<?php

namespace App\Http\Requests\Auth;

use App\Support\BrazilianDocument;
use App\Support\SocialProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public const STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
        'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $state = strtoupper(trim((string) $this->input('state', '')));
        $city = trim((string) $this->input('city', ''));

        $merge = [
            'state' => $state !== '' ? $state : null,
            'city' => $city !== '' ? $city : null,
            'cpf' => SocialProfile::nullable($this->input('cpf'), 18),
        ];
        if ($this->exists('website')) {
            $merge['website'] = SocialProfile::website($this->input('website'));
        }
        if ($this->exists('instagram')) {
            $merge['instagram'] = SocialProfile::nullable($this->input('instagram'), 120);
        }
        if ($this->exists('facebook')) {
            $merge['facebook'] = SocialProfile::nullable($this->input('facebook'));
        }
        if ($this->exists('linkedin')) {
            $merge['linkedin'] = SocialProfile::nullable($this->input('linkedin'));
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'cpf' => ['nullable', 'string', 'max:18', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value !== null && $value !== '' && ! BrazilianDocument::isValid((string) $value)) {
                    $fail('Informe um CPF ou CNPJ válido.');
                }
            }],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', Rule::in(self::STATES)],
            'website' => ['nullable', 'string', 'max:255', 'url'],
            'instagram' => ['nullable', 'string', 'max:120'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:8192'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome.',
            'phone.required' => 'Informe um telefone com DDD.',
            'state.in' => 'Selecione um estado válido.',
            'website.url' => 'Informe um site válido, como https://empresa.com.br',
            'avatar.image' => 'A foto do perfil precisa ser uma imagem.',
            'avatar.max' => 'A foto do perfil deve ter no máximo 8 MB.',
        ];
    }
}
