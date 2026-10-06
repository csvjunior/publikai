<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Variação controlada de um asset do roteiro (Sprint 5.5.4).
 * "Alteração desejada" obrigatória; source validada no controller
 * (pertence ao Script, image pronta). Referências seguem 5.5.3.
 */
class ScriptImageVariationRequest extends FormRequest
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
            'change' => ['required', 'string', 'min:10', 'max:1000'],
            'aspect_ratio' => ['nullable', Rule::in(['1:1', '4:5', '9:16', '16:9'])],
            'image_size' => ['nullable', Rule::in(['1K'])],
            'mime_type' => ['nullable', Rule::in(['image/jpeg', 'image/png'])],
            'purpose' => ['nullable', Rule::in(['cover', 'scene', 'product', 'background', 'other'])],
            'is_primary' => ['sometimes', 'boolean'],
            'reference_ids' => ['nullable', 'array'],
            'reference_ids.*' => ['integer'],
            'visual_dna_only' => ['sometimes', 'boolean'],
        ];
    }
}
