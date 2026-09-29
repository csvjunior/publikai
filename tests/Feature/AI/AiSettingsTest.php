<?php

namespace Tests\Feature\AI;

use App\Enums\UserRole;
use App\Models\AiGeneration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_bloqueado(): void
    {
        $this->get('/settings/ai')->assertRedirect('/login');
        $this->post('/settings/ai/test')->assertRedirect('/login');
    }

    public function test_operator_bloqueado(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->actingAs($operator)->get('/settings/ai')->assertForbidden();
        $this->actingAs($operator)->post('/settings/ai/test')->assertForbidden();
    }

    public function test_admin_ve_status_nao_configurado(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        config()->set('ai.google.enabled', false);

        $this->withoutVite()->actingAs($admin)->get('/settings/ai')
            ->assertOk()
            ->assertSee('Google Gemini')
            ->assertSee('Não configurado', false)
            ->assertDontSee('test-auth-key', false);
    }

    public function test_admin_test_sem_credencial_registra_falha(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        config()->set('ai.google.enabled', true);
        config()->set('ai.google.auth_key', '');

        $response = $this->actingAs($admin)->post('/settings/ai/test');

        $response->assertRedirect();
        $response->assertSessionHas('ai_test');
        $this->assertFalse(session('ai_test')['ok']);
        $this->assertDatabaseHas('ai_generations', [
            'operation' => 'connection_test',
            'status' => 'failed',
            'error_code' => 'credentials_missing',
        ]);
    }

    public function test_admin_test_sucesso_registra_log(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        config()->set('ai.google.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-1',
            'output_text' => '{"status":"ok","message":"tudo certo"}',
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
        ], 200)]);

        $response = $this->actingAs($admin)->post('/settings/ai/test');

        $response->assertRedirect();
        $this->assertTrue(session('ai_test')['ok']);
        $this->assertSame('ok', session('ai_test')['status']);
        $this->assertDatabaseHas('ai_generations', [
            'operation' => 'connection_test',
            'status' => 'success',
            'external_request_id' => 'interaction-1',
        ]);

        $log = AiGeneration::firstWhere('operation', 'connection_test');
        $this->assertSame(10, (int) $log->input_tokens);
        $this->assertSame(5, (int) $log->output_tokens);
        $this->assertNull($log->error_code);
    }
}
