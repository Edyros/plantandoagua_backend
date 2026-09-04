<?php

namespace App\Http\Requests\Campaign;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:10', 'max:100000'],
            'visibility' => ['required', 'string', Rule::in([
                Campaign::VISIBILITY_PUBLIC,
                Campaign::VISIBILITY_INVITE,
            ])],
            'perUserLimit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'payerName' => ['nullable', 'string', 'max:255'],
            'payerCpf' => ['nullable', 'string', 'max:18'],
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
        ];
    }
}
