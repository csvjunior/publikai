<?php

namespace Tests\Feature\Intelligence;

use App\Enums\ContentBlueprintStatus;
use App\Enums\UserRole;
use App\Models\ContentBlueprint;
use App\Models\ReferenceAnalysis;
use App\Models\ReferenceProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ContentBlueprintTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Curiosity UGC Beauty US',
            'description' => 'Short-form UGC structure focused on curiosity and quick product demonstration.',
            'content_type' => 'ugc',
            'objective' => 'Generate curiosity and product consideration.',
            'hook_pattern' => 'Unexpected observation or unmet expectation in the first seconds.',
            'structure_pattern' => 'Curiosity → problem → reveal → demonstration → soft CTA',
            'cta_pattern' => 'Soft recommendation + link in bio',
            'visual_style' => 'Natural handheld UGC with product close-ups and natural lighting.',
            'communication_style' => 'Conversational first-person recommendation.',
            'recommended_duration_seconds' => 15,
            'language' => 'en-US',
            'market' => 'US',
            'niche' => 'Beauty / Skincare',
            'status' => ContentBlueprintStatus::Active->value,
            'notes' => null,
        ], $overrides);
    }

    public function test_guest_bloqueado(): void
    {
        $this->get('/blueprints')->assertRedirect('/login');
        $this->post('/blueprints', $this->validData())->assertRedirect('/login');
    }

    public function test_index_autenticado(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get('/blueprints')
            ->assertOk()
            ->assertSee('Blueprints')
            ->assertSee('Nenhum Blueprint cadastrado', false);
    }

    public function test_create_com_source_manual_automatico(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/blueprints', $this->validData());

        $blueprint = ContentBlueprint::where('slug', 'curiosity-ugc-beauty-us')->firstOrFail();
        $response->assertRedirect(route('blueprints.show', $blueprint));
        $this->assertSame('manual', $blueprint->source_type->value);
    }

    public function test_slug_unico(): void
    {
        $user = User::factory()->create();
        ContentBlueprint::factory()->create(['name' => 'Curiosity UGC', 'slug' => 'curiosity-ugc']);

        $this->actingAs($user)->post('/blueprints', $this->validData(['name' => 'Curiosity UGC']))->assertRedirect();

        $this->assertDatabaseHas('content_blueprints', ['slug' => 'curiosity-ugc-2']);
    }

    public function test_validacao_content_type_e_duration(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/blueprints', $this->validData([
            'name' => '',
            'content_type' => 'filme',
            'recommended_duration_seconds' => 0,
            'language' => 'xx',
            'market' => 'XX',
        ]));

        $response->assertSessionHasErrors(['name', 'content_type', 'recommended_duration_seconds', 'language', 'market']);
        $this->assertDatabaseCount('content_blueprints', 0);
    }

    public function test_update(): void
    {
        $user = User::factory()->create();
        $blueprint = ContentBlueprint::factory()->create(['objective' => 'Antigo']);

        $response = $this->actingAs($user)->put(
            route('blueprints.update', $blueprint),
            $this->validData(['objective' => 'Generate curiosity and product consideration.'])
        );

        $response->assertRedirect(route('blueprints.show', $blueprint));
        $this->assertDatabaseHas('content_blueprints', [
            'id' => $blueprint->id,
            'objective' => 'Generate curiosity and product consideration.',
        ]);
    }

    public function test_matriz_archived(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $active = ContentBlueprint::factory()->create(['status' => ContentBlueprintStatus::Active]);

        $this->actingAs($operator)->put(
            route('blueprints.update', $active),
            $this->validData(['status' => ContentBlueprintStatus::Archived->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('blueprints.update', $active),
            $this->validData(['status' => ContentBlueprintStatus::Paused->value])
        )->assertRedirect();

        $this->actingAs($admin)->put(
            route('blueprints.update', $active),
            $this->validData(['status' => ContentBlueprintStatus::Archived->value])
        )->assertRedirect();

        $this->assertTrue($active->fresh()->isArchived());
    }

    public function test_origem_manual_nao_adulteravel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/blueprints', $this->validData([
            'source_type' => 'ai_assisted',
            'source_reference_profile_id' => 999,
        ]))->assertRedirect();

        $blueprint = ContentBlueprint::firstOrFail();
        $this->assertSame('manual', $blueprint->source_type->value);
        $this->assertNull($blueprint->source_reference_profile_id);
    }

    public function test_source_fks_opcionais(): void
    {
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create();
        $analysis = ReferenceAnalysis::factory()->for($profile, 'profile')->create();

        $blueprint = ContentBlueprint::factory()->create([
            'source_type' => 'ai_assisted',
            'source_reference_profile_id' => $profile->id,
            'source_reference_analysis_id' => $analysis->id,
        ]);

        $response = $this->withoutVite()->actingAs($user)->get(route('blueprints.show', $blueprint))->assertOk();

        $response->assertSee($profile->name, false);
    }

    public function test_rendering_index_show(): void
    {
        $user = User::factory()->create();
        $blueprint = ContentBlueprint::factory()->create(['name' => 'Curiosity UGC Beauty US']);

        $this->withoutVite()->actingAs($user)->get('/blueprints')
            ->assertOk()
            ->assertSee('Curiosity UGC Beauty US', false);

        $this->withoutVite()->actingAs($user)->get(route('blueprints.show', $blueprint))
            ->assertOk()
            ->assertSee('Estratégia', false)
            ->assertSee('Origem', false);
    }

    public function test_sidebar_blueprints_ativa(): void
    {
        $user = User::factory()->create();

        // Blueprints seguem acessíveis por rota/fluxo interno, fora do menu.
        $this->assertTrue(Route::has('blueprints.index'));

        $this->withoutVite()->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertDontSee(route('blueprints.index'), false);
    }
}
