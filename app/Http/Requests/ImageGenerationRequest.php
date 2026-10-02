<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Geração de imagem de teste (Sprint 5.5.0). Só admin (controller).
 */
class ImageGenerationRequest extends FormRequest
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
        ];
    }
}
