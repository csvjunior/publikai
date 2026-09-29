<?php

namespace App\Http\Requests;

use App\Enums\PersonaStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação compartilhada de persona (create/update).
 * Textos descritivos sem restrição excessiva.
 */
abstract class PersonaRequest extends FormRequest
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
            'language' => ['nullable', Rule::in(array_keys(config('locale-options.languages')))],
            'market' => ['nullable', Rule::in(array_keys(config('locale-options.markets')))],
            'audience' => ['nullable', 'string', 'max:255'],
            'personality' => ['nullable', 'string', 'max:255'],
            'tone' => ['nullable', 'string', 'max:255'],
            'communication_style' => ['nullable', 'string', 'max:255'],
            'vocabulary' => ['nullable', 'string', 'max:255'],
            'expressions' => ['nullable', 'string'],
            'content_preferences' => ['nullable', 'string'],
            'avoidances' => ['nullable', 'string'],
            'default_cta_style' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(PersonaStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
