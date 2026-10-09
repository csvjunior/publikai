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
}
