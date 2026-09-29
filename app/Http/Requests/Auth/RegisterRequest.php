<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação do cadastro interno.
 *
 * O controle de acesso (habilitado/desabilitado) é aplicado em
 * authorize(). A conferência do código interno é feita no servidor
 * via RegistrationService (hash_equals), nunca no frontend.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('registration.enabled', false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'registration_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
