<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Contexto para geração por IA (só ids; roteiro vem do provider).
 */
class GenerateContentScriptRequest extends FormRequest
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
        $notArchived = fn (string $table) => Rule::exists($table, 'id')->whereNot('status', 'archived');

        return [
            'product_id' => ['required', $notArchived('products')],
            'content_blueprint_id' => ['required', $notArchived('content_blueprints')],
            'persona_id' => ['required', $notArchived('personas')],
            'avatar_id' => ['required', $notArchived('avatars')],
        ];
    }
}
