<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Narração de teste na página técnica (Sprint 5.6.2, admin-only).
 */
class TestAudioGenerationRequest extends FormRequest
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
            'text' => ['required', 'string', 'min:10', 'max:'.(int) config('ai.google.audio.max_text_length')],
            'voice' => ['required', 'string', Rule::in(array_keys((array) config('ai.google.audio.voices', [])))],
        ];
    }
}
