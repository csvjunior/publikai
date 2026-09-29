<?php

namespace Tests\Feature\CreativeStudio;

use App\Enums\PersonaStatus;
use App\Enums\UserRole;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonaTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Emma US Beauty',
            'language' => 'en-US',
            'market' => 'US',
            'audience' => 'Women 20–40 interested in beauty',
            'personality' => 'Friendly, curious, confident',
            'tone' => 'Natural, casual, conversational',
            'communication_style' => 'First-person UGC and personal recommendation',
            'vocabulary' => 'Everyday American English',
            'expressions' => "I didn't expect this...\nI wish I'd found this sooner...",
            'content_preferences' => 'Curiosity, demonstration, problem/solution',
            'avoidances' => 'Aggressive sales language and exaggerated promises',
            'default_cta_style' => 'Soft recommendation, link in bio',
            'status' => PersonaStatus::Active->value,
            'notes' => null,
        ], $overrides);
    }

    public function test_guest_nao_acessa_personas(): void
    {
        $this->get('/personas')->assertRedirect('/login');
        $this->post('/personas', $this->validData())->assertRedirect('/login');
    }

    public function test_usuario_autenticado_acessa_lista(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get('/personas')
            ->assertOk()
            ->assertSee('Personas')
            ->assertSee('Nenhuma persona cadastrada', false);
    }

    public function test_persona_pode_ser_criada(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/personas', $this->validData());

        $persona = Persona::where('name', 'Emma US Beauty')->firstOrFail();
        $response->assertRedirect(route('personas.show', $persona));
        $this->assertDatabaseHas('personas', ['market' => 'US', 'language' => 'en-US']);
    }

    public function test_dados_invalidos_sao_rejeitados(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/personas', $this->validData([
            'name' => '',
            'language' => 'xx',
            'market' => 'XX',
            'status' => 'invalido',
        ]));

        $response->assertSessionHasErrors(['name', 'language', 'market', 'status']);
        $this->assertDatabaseCount('personas', 0);
    }

    public function test_persona_pode_ser_atualizada(): void
    {
        $user = User::factory()->create();
        $persona = Persona::factory()->create(['tone' => 'Antigo']);

        $response = $this->actingAs($user)->put(
            route('personas.update', $persona),
            $this->validData(['tone' => 'Natural, casual, conversational'])
        );

        $response->assertRedirect(route('personas.show', $persona));
        $this->assertDatabaseHas('personas', ['id' => $persona->id, 'tone' => 'Natural, casual, conversational']);
    }

    public function test_operator_pode_alternar_entre_ativa_e_pausada(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $active = Persona::factory()->create(['status' => PersonaStatus::Active]);
        $paused = Persona::factory()->create(['status' => PersonaStatus::Paused]);

        $this->actingAs($operator)->put(
            route('personas.update', $active),
            $this->validData(['status' => PersonaStatus::Paused->value])
        )->assertRedirect();

        $this->actingAs($operator)->put(
            route('personas.update', $paused),
            $this->validData(['status' => PersonaStatus::Active->value])
        )->assertRedirect();

        $this->assertSame(PersonaStatus::Paused, $active->fresh()->status);
        $this->assertSame(PersonaStatus::Active, $paused->fresh()->status);
    }

    public function test_operator_nao_entra_nem_sai_de_archived(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $active = Persona::factory()->create(['status' => PersonaStatus::Active]);
        $archived = Persona::factory()->create(['status' => PersonaStatus::Archived]);

        $this->actingAs($operator)->put(
            route('personas.update', $active),
            $this->validData(['status' => PersonaStatus::Archived->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('personas.update', $archived),
            $this->validData(['status' => PersonaStatus::Active->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('personas.update', $archived),
            $this->validData(['status' => PersonaStatus::Paused->value])
        )->assertForbidden();

        $this->assertSame(PersonaStatus::Active, $active->fresh()->status);
        $this->assertTrue($archived->fresh()->isArchived());
    }

    public function test_admin_pode_arquivar_e_reativar(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $persona = Persona::factory()->create(['status' => PersonaStatus::Active]);

        $this->actingAs($admin)->put(
            route('personas.update', $persona),
            $this->validData(['status' => PersonaStatus::Archived->value])
        )->assertRedirect();
        $this->assertTrue($persona->fresh()->isArchived());

        $this->actingAs($admin)->put(
            route('personas.update', $persona),
            $this->validData(['status' => PersonaStatus::Active->value])
        )->assertRedirect();
        $this->assertSame(PersonaStatus::Active, $persona->fresh()->status);
    }
}
