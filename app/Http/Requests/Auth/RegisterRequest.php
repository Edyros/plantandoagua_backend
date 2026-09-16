<?php

namespace App\Http\Requests\Auth;

use App\Support\BrazilianDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['required', 'string', 'max:20'],
            'cpf' => ['nullable', 'string', 'max:18', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value !== null && $value !== '' && ! BrazilianDocument::isValid((string) $value)) {
                    $fail('Informe um CPF ou CNPJ válido.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.confirmed' => 'As senhas não coincidem.',
        ];
    }
}
