<?php

namespace App\Http\Requests;

use App\Enums\SocialAccountStatus;
use App\Enums\SocialPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação compartilhada de conta social (create/update).
 * Username único por plataforma (o mesmo nome pode existir em redes diferentes).
 */
abstract class SocialAccountRequest extends FormRequest
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
        $platform = $this->string('platform')->toString();

        return [
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::enum(SocialPlatform::class)],
            'username' => [
                'required', 'string', 'max:100',
                Rule::unique('social_accounts', 'username')
                    ->where('platform', $platform)
                    ->ignore($this->route('socialAccount')),
            ],
            'profile_url' => ['nullable', 'url', 'max:2048'],
            'language' => ['nullable', Rule::in(array_keys(config('locale-options.languages')))],
            'market' => ['nullable', Rule::in(array_keys(config('locale-options.markets')))],
            'niche' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', 'string', 'max:255'],
            'tone' => ['nullable', 'string', 'max:255'],
            'content_style' => ['nullable', 'string', 'max:255'],
            'default_cta' => ['nullable', 'string', 'max:255'],
            'posting_frequency' => ['nullable', 'string', 'max:255'],
            'default_persona_id' => ['nullable', 'integer', Rule::exists('personas', 'id')],
            'default_avatar_id' => ['nullable', 'integer', Rule::exists('avatars', 'id')],
            'status' => ['required', Rule::enum(SocialAccountStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Normaliza o username antes de validar (remove @ acidental e espaços),
     * para que a checagem de unicidade use o mesmo valor persistido.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->username)) {
            $this->merge(['username' => ltrim(trim($this->username), '@')]);
        }
    }
}
