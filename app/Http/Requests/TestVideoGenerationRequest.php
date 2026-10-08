<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Geração de vídeo de teste na página técnica (Sprint 5.6.0, admin-only).
 */
class TestVideoGenerationRequest extends FormRequest
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
            'source_media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
            'aspect_ratio' => ['nullable', Rule::in(['16:9', '9:16'])],
            'duration_seconds' => ['nullable', 'integer', 'in:8'],
        ];
    }
}
