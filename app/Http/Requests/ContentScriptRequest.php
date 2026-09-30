<?php

namespace App\Http\Requests;

use App\Enums\ContentScriptStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação compartilhada de roteiro (create/update manual e revisão).
 * Contexto: só registros ativos ou pausados (nunca arquivados).
 * generation_source nunca vem do usuário.
 */
abstract class ContentScriptRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:255'],
            'hook' => ['required', 'string'],
            'opening' => ['nullable', 'string'],
            'body' => ['required', 'string'],
            'cta' => ['required', 'string'],
            'on_screen_text' => ['nullable', 'string'],
            'visual_direction' => ['nullable', 'string'],
            'voice_direction' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(ContentScriptStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
