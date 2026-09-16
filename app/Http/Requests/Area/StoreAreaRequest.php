<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'vertices' => ['required', 'array', 'min:4'],
            'vertices.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'vertices.*.longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da área.',
            'name.max' => 'O nome da área pode ter no máximo 80 caracteres.',
            'description.max' => 'A descrição pode ter no máximo 1000 caracteres.',
            'vertices.required' => 'Delimite a área no mapa.',
            'vertices.min' => 'Delimite a área com pelo menos 4 cantos no mapa.',
            'vertices.*.latitude.between' => 'A latitude da área precisa ser válida.',
            'vertices.*.longitude.between' => 'A longitude da área precisa ser válida.',
        ];
    }
}
