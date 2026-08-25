<?php

namespace App\Http\Requests\Planting;

use Illuminate\Foundation\Http\FormRequest;

class StorePlantingRequest extends FormRequest
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
            'species' => ['required', 'string', 'max:255'],
            'scientificName' => ['nullable', 'string', 'max:255'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'plantedAt' => ['nullable', 'date'],
            'planted_at' => ['nullable', 'date'],
            'supplierId' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'string', 'max:255'],
            'supplierName' => ['nullable', 'string', 'max:255'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'locationName' => ['nullable', 'string', 'max:255'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'photo' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,heic', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Envie uma foto para comprovar o plantio.',
            'photo.mimes' => 'Use uma imagem JPEG, PNG, WEBP ou HEIC.',
            'photo.max' => 'A foto pode ter no máximo 5 MB.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('plantedAt') && ! $this->filled('planted_at')) {
                $validator->errors()->add('plantedAt', 'A data do plantio é obrigatória.');
            }
        });
    }
}
