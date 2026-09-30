<?php

namespace App\Http\Requests;

use App\Enums\ContentBlueprintStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação compartilhada de blueprint (create/update).
 * content_type reutiliza a taxonomia de config/references.php (mesmo conceito:
 * categoria estrutural do conteúdo). source_type nunca vem do usuário no
 * fluxo manual — definido pelo Service.
 */
abstract class ContentBlueprintRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content_type' => ['nullable', Rule::in(array_keys(config('references.content_types')))],
            'objective' => ['nullable', 'string', 'max:255'],
            'hook_pattern' => ['nullable', 'string', 'max:255'],
            'structure_pattern' => ['nullable', 'string'],
            'cta_pattern' => ['nullable', 'string', 'max:255'],
            'visual_style' => ['nullable', 'string', 'max:255'],
            'communication_style' => ['nullable', 'string', 'max:255'],
            'recommended_duration_seconds' => ['nullable', 'integer', 'min:1'],
            'language' => ['nullable', Rule::in(array_keys(config('locale-options.languages')))],
            'market' => ['nullable', Rule::in(array_keys(config('locale-options.markets')))],
            'niche' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ContentBlueprintStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
