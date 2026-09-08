<?php

namespace App\Http\Requests\Planting;

use Illuminate\Foundation\Http\FormRequest;

class StorePlantingUpdateRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:2000'],
            'observedAt' => ['nullable', 'date'],
            'observed_at' => ['nullable', 'date'],
            'photo' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,heic', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Envie uma foto para registrar a evolução.',
            'photo.mimes' => 'Use uma imagem JPEG, PNG, WEBP ou HEIC.',
            'photo.max' => 'A foto pode ter no máximo 5 MB.',
        ];
    }
}
