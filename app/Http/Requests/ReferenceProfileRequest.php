<?php

namespace App\Http\Requests;

use App\Enums\ReferenceProfileStatus;
use App\Enums\SocialPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação compartilhada de perfil de referência (create/update).
 */
abstract class ReferenceProfileRequest extends FormRequest
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
            'platform' => ['required', Rule::enum(SocialPlatform::class)],
            'username' => ['nullable', 'string', 'max:100'],
            'profile_url' => ['required', 'url', 'max:2048'],
            'language' => ['nullable', Rule::in(array_keys(config('locale-options.languages')))],
            'market' => ['nullable', Rule::in(array_keys(config('locale-options.markets')))],
            'niche' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ReferenceProfileStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->username)) {
            $this->merge(['username' => ltrim(trim($this->username), '@')]);
        }
    }
}
