<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Criação de conteúdo orientada a resultado (Sprint 5.6.4).
 * Produto/Persona/Avatar obrigatórios (reutilizáveis, sem redigitar DNA);
 * Blueprint fica no avançado (default automático).
 */
class StoreContentRequest extends FormRequest
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
            'type' => ['required', Rule::in(['video', 'image'])],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'persona_id' => ['required', 'integer', 'exists:personas,id'],
            'avatar_id' => ['required', 'integer', 'exists:avatars,id'],
            'objective' => ['required', 'string', 'min:10', 'max:500'],
            'guidance' => ['nullable', 'string', 'max:500'],
            'content_blueprint_id' => ['nullable', 'integer', 'exists:content_blueprints,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Selecione um produto.',
            'product_id.integer' => 'Selecione um produto válido.',
            'product_id.exists' => 'Selecione um produto válido.',
            'persona_id.required' => 'Selecione uma persona.',
            'persona_id.integer' => 'Selecione uma persona válida.',
            'persona_id.exists' => 'Selecione uma persona válida.',
            'avatar_id.required' => 'Selecione um avatar.',
            'avatar_id.integer' => 'Selecione um avatar válido.',
            'avatar_id.exists' => 'Selecione um avatar válido.',
            'objective.required' => 'Informe o objetivo do conteúdo.',
            'objective.string' => 'Informe o objetivo do conteúdo.',
            'objective.min' => 'Informe um objetivo com pelo menos :min caracteres.',
            'objective.max' => 'Informe um objetivo com no máximo :max caracteres.',
            'guidance.string' => 'Informe uma orientação válida.',
            'guidance.max' => 'A orientação pode ter no máximo :max caracteres.',
            'content_blueprint_id.integer' => 'Selecione uma estrutura válida.',
            'content_blueprint_id.exists' => 'Selecione uma estrutura válida.',
        ];
    }
}
