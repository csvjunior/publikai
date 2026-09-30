<?php

namespace Tests\Feature\Intelligence;

use App\Enums\ReferenceAnalysisStatus;
use App\Enums\ReferenceContentStatus;
use App\Enums\ReferenceProfileStatus;
use App\Models\ReferenceAnalysis;
use App\Models\ReferenceContent;
use App\Models\ReferenceProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReferenceAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private function analysisPayload(array $overrides = []): array
    {
        $lists = [
            'dominant_hooks' => ['Curiosity opening'],
            'content_structures' => ['Problem → reveal → demo → CTA'],
            'cta_patterns' => ['Link in bio'],
            'visual_patterns' => ['Natural handheld UGC'],
            'communication_patterns' => ['First-person recommendation'],
            'audience_signals' => ['Beauty enthusiasts'],
            'content_angles' => ['Problem/solution'],
            'repeated_patterns' => ['Fast demonstration'],
            'risks' => ['Overpromising results'],
            'recommendations' => ['Keep videos under 30 seconds'],
        ];

        return array_merge([
            'summary' => 'Consistent UGC patterns observed.',
            'confidence' => 0.82,
        ], $lists, $overrides);
    }

    private function enableAi(): void
    {
        config()->set('ai.google.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    private function fakeSuccess(array $overrides = []): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-9',
            'output_text' => json_encode($this->analysisPayload($overrides)),
        ], 200)]);
    }

    public function test_guest_bloqueado(): void
    {
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);

        $this->post(route('references.analyses.store', $profile))->assertRedirect('/login');
    }

    public function test_usuario_autenticado_pode_analisar(): void
    {
        $this->enableAi();
        $this->fakeSuccess();
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $response = $this->actingAs($user)->post(route('references.analyses.store', $profile));

        $response->assertRedirect();
        $analysis = ReferenceAnalysis::firstWhere('reference_profile_id', $profile->id);
        $this->assertSame(ReferenceAnalysisStatus::Success, $analysis->status);
        $this->assertSame('Consistent UGC patterns observed.', $analysis->summary);
        $this->assertSame(0.82, $analysis->confidence);
        $this->assertSame(['Curiosity opening'], $analysis->dominant_hooks);
    }

    public function test_profile_sem_conteudo_ativo_rejeitado(): void
    {
        $this->enableAi();
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);

        $response = $this->actingAs($user)->post(route('references.analyses.store', $profile));

        $response->assertRedirect();
        $response->assertSessionHas('analysis_notice');
        $this->assertDatabaseCount('reference_analyses', 0);
        Http::assertNothingSent();
    }

    public function test_failed_persiste_erro_sanitizado(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 503)]);
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();

        $this->assertDatabaseHas('reference_analyses', [
            'reference_profile_id' => $profile->id,
            'status' => ReferenceAnalysisStatus::Failed,
            'error_code' => 'service_unavailable',
        ]);
    }

    public function test_timeout_resulta_failed(): void
    {
        config()->set('ai.google.enabled', false);
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();

        $this->assertDatabaseHas('reference_analyses', [
            'reference_profile_id' => $profile->id,
            'status' => ReferenceAnalysisStatus::Failed,
            'error_code' => 'provider_disabled',
        ]);
    }

    public function test_schema_invalido_resulta_failed(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'x',
            'output_text' => json_encode(['summary' => 'Sem listas']),
        ], 200)]);
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();

        $this->assertDatabaseHas('reference_analyses', [
            'reference_profile_id' => $profile->id,
            'status' => ReferenceAnalysisStatus::Failed,
            'error_code' => 'schema_mismatch',
        ]);
    }

    public function test_confidence_invalida_resulta_failed(): void
    {
        $this->enableAi();
        $this->fakeSuccess(['confidence' => 2]);
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();

        $this->assertDatabaseHas('reference_analyses', [
            'reference_profile_id' => $profile->id,
            'status' => ReferenceAnalysisStatus::Failed,
            'error_code' => 'schema_mismatch',
        ]);
    }

    public function test_ai_generations_continua_criado_e_historico_preservado(): void
    {
        $this->enableAi();
        $this->fakeSuccess();
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();
        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();

        $this->assertSame(2, ReferenceAnalysis::where('reference_profile_id', $profile->id)->count());
        $this->assertDatabaseHas('ai_generations', [
            'operation' => 'reference_analysis',
            'status' => 'success',
        ]);
    }

    public function test_retry_cria_nova_analise_apos_falha(): void
    {
        $this->enableAi();
        // Fakes acumulam (primeiro vence): sequência única — 503,503 (retry) e depois 200.
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => 'x'], 503)
            ->push(['error' => 'x'], 503)
            ->push(['id' => 'ok', 'output_text' => json_encode($this->analysisPayload())], 200),
        ]);
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();
        $this->assertSame(1, ReferenceAnalysis::where('reference_profile_id', $profile->id)->count());

        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();

        $this->assertSame(2, ReferenceAnalysis::where('reference_profile_id', $profile->id)->count());
        $this->assertSame(1, ReferenceAnalysis::where('reference_profile_id', $profile->id)
            ->where('status', ReferenceAnalysisStatus::Success)->count());
    }

    public function test_pendente_impede_duplicata(): void
    {
        $this->enableAi();
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);
        ReferenceAnalysis::factory()->for($profile, 'profile')->create([
            'status' => ReferenceAnalysisStatus::Pending,
        ]);

        $response = $this->actingAs($user)->post(route('references.analyses.store', $profile));

        $response->assertRedirect();
        $response->assertSessionHas('analysis_notice', 'Já existe uma análise em andamento para esta referência.');
        $this->assertSame(1, ReferenceAnalysis::where('reference_profile_id', $profile->id)->count());
        Http::assertNothingSent();
    }

    public function test_latest_successful_em_destaque(): void
    {
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);
        $success = ReferenceAnalysis::factory()->for($profile, 'profile')->create([
            'status' => ReferenceAnalysisStatus::Success,
            'summary' => 'Análise vencedora consolidada.',
            'created_at' => now()->subHour(),
        ]);
        ReferenceAnalysis::factory()->for($profile, 'profile')->create([
            'status' => ReferenceAnalysisStatus::Failed,
        ]);

        $response = $this->withoutVite()->actingAs($user)->get(route('references.show', $profile))->assertOk();

        $response->assertSee('Análise vencedora consolidada.', false);
        $response->assertSee('Histórico de análises', false);
        $this->assertTrue($success->isSuccess());
    }

    public function test_profile_funciona_apos_falha(): void
    {
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);
        ReferenceAnalysis::factory()->for($profile, 'profile')->create([
            'status' => ReferenceAnalysisStatus::Failed,
            'error_code' => 'service_unavailable',
        ]);

        $response = $this->withoutVite()->actingAs($user)->get(route('references.show', $profile))->assertOk();

        $response->assertSee('Falha na análise', false);
        $response->assertSee('temporariamente indisponível', false);
    }

    public function test_success_exibe_flash_de_sucesso(): void
    {
        $this->enableAi();
        $this->fakeSuccess();
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $response = $this->actingAs($user)->post(route('references.analyses.store', $profile));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Análise concluída. Veja o resultado abaixo.');
        $response->assertSessionMissing('analysis_error');
    }

    public function test_failed_service_unavailable_exibe_mensagem_amigavel(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 503)]);
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $response = $this->actingAs($user)->post(route('references.analyses.store', $profile));

        $response->assertRedirect();
        $response->assertSessionMissing('status');
        $response->assertSessionHas('analysis_error', 'O serviço de IA está temporariamente indisponível. Tente novamente mais tarde.');
    }

    public function test_failed_timeout_exibe_mensagem_amigavel(): void
    {
        $this->enableAi();
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $response = $this->actingAs($user)->post(route('references.analyses.store', $profile));

        $response->assertRedirect();
        $response->assertSessionMissing('status');
        $response->assertSessionHas('analysis_error', 'O serviço de IA demorou mais que o esperado para responder. Tente novamente.');
    }

    public function test_timeout_controlado_nao_quebra_e_nao_prende_analise(): void
    {
        $this->enableAi();
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();

        $this->assertDatabaseHas('reference_analyses', [
            'reference_profile_id' => $profile->id,
            'status' => ReferenceAnalysisStatus::Failed,
            'error_code' => 'timeout',
        ]);
        $this->assertDatabaseHas('ai_generations', [
            'operation' => 'reference_analysis',
            'status' => 'failed',
            'error_code' => 'timeout',
        ]);
        $this->assertSame(0, ReferenceAnalysis::where('reference_profile_id', $profile->id)
            ->whereIn('status', [ReferenceAnalysisStatus::Pending, ReferenceAnalysisStatus::Processing])
            ->count());

        $this->withoutVite()->actingAs($user)->get(route('references.show', $profile))->assertOk();
    }

    public function test_orcamento_sincrono_vem_de_config(): void
    {
        // Prova que o budget é configurável (sem hardcode): valores
        // customizados fluem e o pior caso teórico segue < 30s.
        config()->set('ai.google.timeout', 7);
        config()->set('ai.google.connect_timeout', 3);

        $this->enableAi();
        $this->fakeSuccess();
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $this->actingAs($user)->post(route('references.analyses.store', $profile))->assertRedirect();

        $this->assertSame(7, config('ai.google.timeout'));
        $this->assertSame(3, config('ai.google.connect_timeout'));
        $this->assertDatabaseHas('reference_analyses', [
            'reference_profile_id' => $profile->id,
            'status' => ReferenceAnalysisStatus::Success,
        ]);
        // Pior caso: 2 tentativas × timeout + overhead < max_execution_time (30s).
        $this->assertLessThanOrEqual(22, (int) config('ai.google.timeout') * 2 + 2);
    }

    public function test_failed_generico_exibe_mensagem_segura(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 401)]);
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);

        $response = $this->actingAs($user)->post(route('references.analyses.store', $profile));

        $response->assertRedirect();
        $response->assertSessionMissing('status');
        $response->assertSessionHas('analysis_error', 'Não foi possível concluir a análise agora. Tente novamente mais tarde.');
    }

    public function test_horarios_convertidos_para_display_timezone(): void
    {
        config()->set('app.display_timezone', 'America/Sao_Paulo');
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);
        $moment = Carbon::create(2026, 9, 29, 22, 52, 0, 'UTC');
        ReferenceAnalysis::factory()->for($profile, 'profile')->create([
            'status' => ReferenceAnalysisStatus::Success,
            'completed_at' => $moment,
            'created_at' => $moment,
        ]);

        $response = $this->withoutVite()->actingAs($user)->get(route('references.show', $profile))->assertOk();

        $response->assertSee('29/09/2026 19:52', false);
        $response->assertDontSee('22:52', false);
    }
}
