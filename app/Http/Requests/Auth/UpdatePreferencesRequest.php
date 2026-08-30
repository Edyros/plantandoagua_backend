<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
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
            'appearOnCommunityMap' => ['sometimes', 'boolean'],
            'publicProfile' => ['sometimes', 'boolean'],
            'showCityOnProfile' => ['sometimes', 'boolean'],
            'pinPrecision' => ['sometimes', 'string', Rule::in(['exact', 'approximate'])],
            'monthlyGoal' => ['sometimes', 'integer', Rule::in([5, 10, 20, 50])],
            'defaultMapFilter' => ['sometimes', 'string', Rule::in(['mine', 'community'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'appearOnCommunityMap.boolean' => 'Informe se deseja aparecer no mapa da comunidade.',
            'publicProfile.boolean' => 'Informe se o perfil é público.',
            'showCityOnProfile.boolean' => 'Informe se a cidade aparece no perfil.',
            'pinPrecision.in' => 'Escolha a precisão exata ou aproximada.',
            'monthlyGoal.in' => 'Escolha uma meta de 5, 10, 20 ou 50 árvores.',
            'defaultMapFilter.in' => 'Escolha Meus ou Comunidade como filtro padrão.',
        ];
    }
}
