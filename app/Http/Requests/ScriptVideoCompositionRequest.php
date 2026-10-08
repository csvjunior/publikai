<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Composição local de vídeo (Sprint 5.6.1).
 * Itens ordenados por position; trims em segundos (server converte p/ ms);
 * duração de imagem em segundos. Vínculos validados no controller/service.
 */
class ScriptVideoCompositionRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
            'items.*.position' => ['required', 'integer', 'min:1'],
            'items.*.trim_start_s' => ['nullable', 'numeric', 'min:0'],
            'items.*.trim_end_s' => ['nullable', 'numeric', 'min:0'],
            'items.*.image_duration_s' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
