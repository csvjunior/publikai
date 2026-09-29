<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Regra de negócio do cadastro interno do Publikai.
 *
 * - Bloqueia tudo quando REGISTRATION_ENABLED=false (403).
 * - Quando REGISTRATION_CODE estiver configurado (não vazio), exige
 *   o código e compara somente no servidor com hash_equals.
 * - O código nunca é exposto ao frontend nem registrado em logs.
 * - O primeiro usuário criado recebe a função admin; os demais,
 *   operator. Expansão futura via Gates/Policies.
 */
class RegistrationService
{
    /**
     * @param  array{name: string, email: string, password: string, registration_code?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function register(array $data): User
    {
        if (! config('registration.enabled', false)) {
            abort(403, 'O cadastro está desabilitado.');
        }

        $this->validateCode($data['registration_code'] ?? null);

        $role = User::query()->exists() ? UserRole::Operator : UserRole::Admin;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
        ]);

        event(new Registered($user));

        return $user;
    }

    /**
     * @throws ValidationException
     */
    protected function validateCode(?string $provided): void
    {
        $expected = (string) config('registration.code', '');

        if ($expected === '') {
            return;
        }

        if (! is_string($provided) || $provided === '' || ! hash_equals($expected, $provided)) {
            throw ValidationException::withMessages([
                'registration_code' => 'Código de cadastro inválido.',
            ]);
        }
    }
}
