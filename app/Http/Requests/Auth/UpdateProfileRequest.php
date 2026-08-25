<?php

namespace App\Http\Requests\Auth;

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
        $cpf = trim((string) $this->input('cpf', ''));

        $this->merge([
            'state' => $state !== '' ? $state : null,
            'city' => $city !== '' ? $city : null,
            'cpf' => $cpf !== '' ? $cpf : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', Rule::in(self::STATES)],
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
            'avatar.image' => 'A foto do perfil precisa ser uma imagem.',
            'avatar.max' => 'A foto do perfil deve ter no máximo 8 MB.',
        ];
    }
}
