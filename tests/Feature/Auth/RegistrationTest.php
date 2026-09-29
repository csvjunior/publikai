<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastro_habilitado_sem_codigo_cria_usuario(): void
    {
        config()->set('registration.enabled', true);
        config()->set('registration.code', '');

        $response = $this->post('/register', [
            'name' => 'Operadora Interna',
            'email' => 'operadora@jaguartec.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'operadora@jaguartec.test']);
    }

    public function test_primeiro_usuario_recebe_funcao_admin(): void
    {
        config()->set('registration.enabled', true);
        config()->set('registration.code', '');

        $this->post('/register', [
            'name' => 'Primeira Usuária',
            'email' => 'admin@jaguartec.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertEquals(UserRole::Admin, User::where('email', 'admin@jaguartec.test')->first()->role);
    }

    public function test_segundo_usuario_recebe_funcao_operator(): void
    {
        config()->set('registration.enabled', true);
        config()->set('registration.code', '');
        User::factory()->create();

        $this->post('/register', [
            'name' => 'Segunda Usuária',
            'email' => 'operator@jaguartec.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertEquals(UserRole::Operator, User::where('email', 'operator@jaguartec.test')->first()->role);
    }

    public function test_cadastro_desabilitado_retorna_403(): void
    {
        config()->set('registration.enabled', false);

        $this->get('/register')->assertForbidden();

        $this->post('/register', [
            'name' => 'Bloqueada',
            'email' => 'bloqueada@jaguartec.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'bloqueada@jaguartec.test']);
    }

    public function test_codigo_de_cadastro_invalido_rejeita(): void
    {
        config()->set('registration.enabled', true);
        config()->set('registration.code', 'CODIGO-SECRETO');

        $response = $this->post('/register', [
            'name' => 'Invasora',
            'email' => 'invasora@jaguartec.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'registration_code' => 'codigo-errado',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('registration_code');
        $this->assertDatabaseMissing('users', ['email' => 'invasora@jaguartec.test']);
    }

    public function test_codigo_de_cadastro_valido_permite(): void
    {
        config()->set('registration.enabled', true);
        config()->set('registration.code', 'CODIGO-SECRETO');

        $response = $this->post('/register', [
            'name' => 'Interna Autorizada',
            'email' => 'autorizada@jaguartec.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'registration_code' => 'CODIGO-SECRETO',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'autorizada@jaguartec.test']);
    }
}
