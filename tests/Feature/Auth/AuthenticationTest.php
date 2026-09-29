<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_valido_autentica_e_redireciona_para_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_login_invalido_nao_autentica(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'senha-errada',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_dashboard_exige_autenticacao(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_usuario_autenticado_acessa_dashboard(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Publikai');
    }

    public function test_logout_encerra_sessao(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
