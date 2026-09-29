<?php

namespace App\Http\Requests;

use App\Enums\AvatarStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação compartilhada de avatar (create/update).
 */
abstract class AvatarRequest extends FormRequest
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
            'apparent_age' => ['nullable', 'string', 'max:20'],
            'gender_presentation' => ['nullable', 'string', 'max:255'],
            'ethnicity_description' => ['nullable', 'string', 'max:255'],
            'hair' => ['nullable', 'string', 'max:255'],
            'eyes' => ['nullable', 'string', 'max:255'],
            'skin' => ['nullable', 'string', 'max:255'],
            'body_description' => ['nullable', 'string', 'max:255'],
            'default_clothing' => ['nullable', 'string', 'max:255'],
            'visual_style' => ['nullable', 'string', 'max:255'],
            'preferred_scenarios' => ['nullable', 'string', 'max:255'],
            'voice_description' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', Rule::in(array_keys(config('locale-options.languages')))],
            'market' => ['nullable', Rule::in(array_keys(config('locale-options.markets')))],
            'reference_notes' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(AvatarStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
