<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Merge vídeo + narração (Sprint 5.6.3).
 * IDs resolvidos e validados server-side (mesmo Script, tipos, merged fora).
 */
class ScriptAudioVideoMergeRequest extends FormRequest
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
            'video_media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
            'audio_media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
        ];
    }
}
