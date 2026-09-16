<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:80'],
            'area' => ['sometimes', 'nullable', 'array'],
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
            'area.vertices.min' => 'Delimite a área com pelo menos 4 cantos no mapa.',
            'area.vertices.*.latitude.between' => 'A latitude da área precisa ser válida.',
            'area.vertices.*.longitude.between' => 'A longitude da área precisa ser válida.',
        ];
    }
}
