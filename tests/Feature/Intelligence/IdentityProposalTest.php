<?php

namespace Tests\Feature\Intelligence;

use App\Enums\IdentityProposalStatus;
use App\Enums\ReferenceAnalysisStatus;
use App\Models\Avatar;
use App\Models\IdentityProposal;
use App\Models\Persona;
use App\Models\ReferenceAnalysis;
use App\Models\ReferenceContent;
use App\Models\ReferenceProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdentityProposalTest extends TestCase
{
    use RefreshDatabase;

    private function personaPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Emma US Beauty',
            'language' => 'en-US',
            'market' => 'US',
            'audience' => 'Women 20–40',
            'personality' => 'Friendly, curious',
            'tone' => 'Natural, casual',
            'communication_style' => 'First-person UGC',
            'vocabulary' => 'Everyday American English',
            'expressions' => 'So good...',
            'content_preferences' => 'Curiosity, demo',
            'avoidances' => 'Hype',
            'default_cta_style' => 'Soft recommendation',
            'notes' => null,
        ], $overrides);
    }

    private function avatarPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Emma',
            'apparent_age' => '27',
            'gender_presentation' => 'Female',
            'ethnicity_description' => null,
            'hair' => 'Brunette',
            'eyes' => 'Brown',
            'skin' => 'Warm',
            'body_description' => null,
            'default_clothing' => 'Casual neutrals',
            'visual_style' => 'Natural UGC creator',
            'preferred_scenarios' => 'Vanity, daylight',
            'voice_description' => 'Friendly young female',
            'language' => 'en-US',
            'market' => 'US',
            'reference_notes' => null,
            'notes' => null,
        ], $overrides);
    }

    private function successPayload(): array
    {
        return [
            'persona' => $this->personaPayload(),
            'avatar' => $this->avatarPayload(),
            'rationale' => [
                'persona' => ['Matches observed tone.'],
                'avatar' => ['Matches observed style.'],
            ],
        ];
    }

    private function readyProfile(): ReferenceProfile
    {
        $profile = ReferenceProfile::factory()->create(['status' => 'active']);
        ReferenceContent::factory()->for($profile, 'profile')->create(['status' => 'active']);
        ReferenceAnalysis::factory()->for($profile, 'profile')->create([
            'status' => ReferenceAnalysisStatus::Success,
        ]);

        return $profile;
    }

    private function enableAi(): void
    {
        config()->set('ai.google.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    public function test_guest_bloqueado(): void
    {
        $profile = ReferenceProfile::factory()->create();

        $this->post(route('references.proposals.store', $profile))->assertRedirect('/login');
    }

    public function test_authenticated_pode_gerar(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'i-1', 'output_text' => json_encode($this->successPayload()),
        ], 200)]);
        $user = User::factory()->create();
        $profile = $this->readyProfile();

        $response = $this->actingAs($user)->post(route('references.proposals.store', $profile));

        $response->assertRedirect();
        $proposal = IdentityProposal::firstWhere('reference_profile_id', $profile->id);
        $this->assertSame(IdentityProposalStatus::Ready, $proposal->status);
        $this->assertSame('Emma US Beauty', $proposal->persona_data['name']);
        $this->assertSame('Emma', $proposal->avatar_data['name']);
        $this->assertSame(['Matches observed tone.'], $proposal->rationale['persona']);
    }

    public function test_sem_successful_analysis_rejeitado(): void
    {
        $this->enableAi();
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post(route('references.proposals.store', $profile));

        $response->assertRedirect();
        $response->assertSessionHas('proposal_notice');
        $this->assertDatabaseCount('identity_proposals', 0);
        Http::assertNothingSent();
    }

    public function test_ai_disabled_rejeitado(): void
    {
        config()->set('ai.google.enabled', false);
        $user = User::factory()->create();
        $profile = $this->readyProfile();

        $response = $this->actingAs($user)->post(route('references.proposals.store', $profile));

        $response->assertRedirect();
        $response->assertSessionHas('proposal_notice');
        $this->assertDatabaseCount('identity_proposals', 0);
        Http::assertNothingSent();
    }

    public function test_pendente_bloqueia_duplicata(): void
    {
        $this->enableAi();
        $user = User::factory()->create();
        $profile = $this->readyProfile();
        IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Pending,
        ]);

        $response = $this->actingAs($user)->post(route('references.proposals.store', $profile));

        $response->assertRedirect();
        $response->assertSessionHas('proposal_notice', 'Já existe uma proposta em processamento.');
        $this->assertSame(1, IdentityProposal::where('reference_profile_id', $profile->id)->count());
        Http::assertNothingSent();
    }

    public function test_failed_cria_failed(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 503)]);
        $user = User::factory()->create();
        $profile = $this->readyProfile();

        $this->actingAs($user)->post(route('references.proposals.store', $profile))->assertRedirect();

        $this->assertDatabaseHas('identity_proposals', [
            'reference_profile_id' => $profile->id,
            'status' => IdentityProposalStatus::Failed,
            'error_code' => 'service_unavailable',
        ]);
    }

    public function test_schema_invalido_falha(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'x', 'output_text' => json_encode(['persona' => [], 'avatar' => []]),
        ], 200)]);
        $user = User::factory()->create();
        $profile = $this->readyProfile();

        $this->actingAs($user)->post(route('references.proposals.store', $profile))->assertRedirect();

        $this->assertDatabaseHas('identity_proposals', [
            'reference_profile_id' => $profile->id,
            'status' => IdentityProposalStatus::Failed,
            'error_code' => 'schema_mismatch',
        ]);
    }

    public function test_edit_proposal(): void
    {
        $user = User::factory()->create();
        $profile = $this->readyProfile();
        $proposal = IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Ready,
        ]);

        $response = $this->actingAs($user)->put(
            route('references.proposals.update', [$profile, $proposal]),
            [
                'persona' => $this->personaPayload(['tone' => 'Playful, warm']),
                'avatar' => $this->avatarPayload(),
            ]
        );

        $response->assertRedirect();
        $this->assertSame('Playful, warm', $proposal->fresh()->persona_data['tone']);
        $this->assertSame(IdentityProposalStatus::Ready, $proposal->fresh()->status);
    }

    public function test_validation_edit(): void
    {
        $user = User::factory()->create();
        $profile = $this->readyProfile();
        $proposal = IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Ready,
        ]);

        $response = $this->actingAs($user)->put(
            route('references.proposals.update', [$profile, $proposal]),
            [
                'persona' => $this->personaPayload(['name' => '', 'language' => 'xx']),
                'avatar' => $this->avatarPayload(),
            ]
        );

        $response->assertSessionHasErrors(['persona.name', 'persona.language']);
        $this->assertSame(IdentityProposalStatus::Ready, $proposal->fresh()->status);
    }

    public function test_apply_cria_persona_avatar(): void
    {
        $user = User::factory()->create();
        $profile = $this->readyProfile();
        $proposal = IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Ready,
            'persona_data' => $this->personaPayload(),
            'avatar_data' => $this->avatarPayload(),
        ]);

        $response = $this->actingAs($user)->post(
            route('references.proposals.apply', [$profile, $proposal])
        );

        $response->assertRedirect();
        $proposal->refresh();
        $this->assertSame(IdentityProposalStatus::Applied, $proposal->status);
        $this->assertNotNull($proposal->applied_at);

        $persona = Persona::findOrFail($proposal->applied_persona_id);
        $avatar = Avatar::findOrFail($proposal->applied_avatar_id);
        $this->assertSame('active', $persona->status->value);
        $this->assertSame('active', $avatar->status->value);
        $this->assertSame('Emma US Beauty', $persona->name);
        $this->assertSame('Emma', $avatar->name);
    }

    public function test_applied_nao_aplica_duas_vezes(): void
    {
        $user = User::factory()->create();
        $profile = $this->readyProfile();
        $proposal = IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Ready,
            'persona_data' => $this->personaPayload(),
            'avatar_data' => $this->avatarPayload(),
        ]);

        $this->actingAs($user)->post(route('references.proposals.apply', [$profile, $proposal]))->assertRedirect();
        $this->actingAs($user)->post(route('references.proposals.apply', [$profile, $proposal]))->assertRedirect();

        $this->assertSame(1, Persona::where('name', 'Emma US Beauty')->count());
        $this->assertSame(1, Avatar::where('name', 'Emma')->count());
    }

    public function test_discard_funciona_e_bloqueia_apply(): void
    {
        $user = User::factory()->create();
        $profile = $this->readyProfile();
        $proposal = IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Ready,
            'persona_data' => $this->personaPayload(),
            'avatar_data' => $this->avatarPayload(),
        ]);

        $this->actingAs($user)->post(route('references.proposals.discard', [$profile, $proposal]))->assertRedirect();

        $this->assertSame(IdentityProposalStatus::Discarded, $proposal->fresh()->status);
        $this->assertDatabaseHas('identity_proposals', ['id' => $proposal->id]);

        $this->actingAs($user)->post(route('references.proposals.apply', [$profile, $proposal]))->assertRedirect();

        $this->assertSame(0, Persona::count());
        $this->assertSame(0, Avatar::count());
    }

    public function test_historico_e_destaque(): void
    {
        $user = User::factory()->create();
        $profile = $this->readyProfile();
        IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Ready,
            'persona_data' => $this->personaPayload(['name' => 'Ready One']),
            'created_at' => now()->subHour(),
        ]);
        IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Failed,
        ]);

        $response = $this->withoutVite()->actingAs($user)->get(route('references.show', $profile))->assertOk();

        $response->assertSee('Ready One', false);
        $response->assertSee('Histórico de propostas', false);
    }

    public function test_protecao_dirty_state_presente_no_markup(): void
    {
        $user = User::factory()->create();
        $profile = $this->readyProfile();
        IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Ready,
            'persona_data' => $this->personaPayload(),
            'avatar_data' => $this->avatarPayload(),
        ]);

        $response = $this->withoutVite()->actingAs($user)->get(route('references.show', $profile))->assertOk();

        $response->assertSee('id="proposal-review-form"', false);
        $response->assertSee('id="proposal-apply-button"', false);
        $response->assertSee('Salve as alterações da revisão antes de aplicar a proposta.', false);
    }

    public function test_failure_nao_apaga_ready_anterior(): void
    {
        $this->enableAi();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'x'], 503)]);
        $user = User::factory()->create();
        $profile = $this->readyProfile();
        IdentityProposal::factory()->for($profile, 'profile')->create([
            'status' => IdentityProposalStatus::Ready,
            'persona_data' => $this->personaPayload(['name' => 'Ready One']),
        ]);

        $this->actingAs($user)->post(route('references.proposals.store', $profile))->assertRedirect();

        $this->assertSame(1, IdentityProposal::where('reference_profile_id', $profile->id)
            ->where('status', IdentityProposalStatus::Ready)->count());
    }

    public function test_disabled_rejeitado_sem_criar_nada(): void
    {
        config()->set('ai.google.enabled', false);
        $user = User::factory()->create();
        $profile = $this->readyProfile();

        $response = $this->actingAs($user)->post(route('references.proposals.store', $profile));

        $response->assertRedirect();
        $response->assertSessionHas('proposal_notice');
        $this->assertDatabaseCount('identity_proposals', 0);
        Http::assertNothingSent();

        $this->withoutVite()->actingAs($user)->get(route('references.show', $profile))->assertOk();
    }

    public function test_timeout_nao_quebra_pagina(): void
    {
        $this->enableAi();
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });
        $user = User::factory()->create();
        $profile = $this->readyProfile();

        $this->actingAs($user)->post(route('references.proposals.store', $profile))->assertRedirect();

        $this->withoutVite()->actingAs($user)->get(route('references.show', $profile))->assertOk();
        $this->assertDatabaseHas('identity_proposals', [
            'reference_profile_id' => $profile->id,
            'status' => IdentityProposalStatus::Failed,
            'error_code' => 'timeout',
        ]);
    }
}
