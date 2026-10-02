<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Geração contextual de imagem a partir de roteiro (Sprint 5.5.1).
 * Prompt revisável; opções e purpose controlados.
 */
class ScriptImageGenerationRequest extends FormRequest
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
            'prompt' => ['required', 'string', 'min:10', 'max:2000'],
            'aspect_ratio' => ['nullable', Rule::in(['1:1', '4:5', '9:16', '16:9'])],
            'image_size' => ['nullable', Rule::in(['1K'])],
            'mime_type' => ['nullable', Rule::in(['image/jpeg', 'image/png'])],
            'purpose' => ['nullable', Rule::in(['cover', 'scene', 'product', 'background', 'other'])],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
