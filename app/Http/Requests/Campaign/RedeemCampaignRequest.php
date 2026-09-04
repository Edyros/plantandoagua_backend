<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class RedeemCampaignRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:24'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código da campanha.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code', '')));
        $code = preg_replace('/\s+/', '', $code) ?? $code;

        $this->merge(['code' => $code]);
    }
}
