<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Geração de vídeo image-to-video (Sprint 5.6.0).
 * Source obrigatória (MediaAsset image existente); formato/duração fixos
 * server-side (9:16, 8s); vínculo com o Script validado no controller.
 */
class ScriptVideoGenerationRequest extends FormRequest
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
            'motion' => ['required', 'string', 'min:10', 'max:1000'],
            'source_media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
        ];
    }
}
