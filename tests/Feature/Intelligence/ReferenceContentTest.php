<?php

namespace Tests\Feature\Intelligence;

use App\Enums\ReferenceContentStatus;
use App\Enums\UserRole;
use App\Models\ReferenceContent;
use App\Models\ReferenceProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceContentTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'url' => 'https://instagram.com/reel/exemplo',
            'title' => null,
            'content_type' => 'ugc',
            'observed_hook' => 'Curiosity opening',
            'observed_structure' => 'Problem → reveal → demo → CTA',
            'observed_cta' => 'Link in bio',
            'observed_style' => 'Natural handheld UGC',
            'duration_seconds' => 45,
            'performance_notes' => 'High views and strong comment activity',
            'why_it_works' => 'Shows the problem in the first second.',
            'status' => ReferenceContentStatus::Active->value,
            'notes' => null,
        ], $overrides);
    }

    public function test_create_com_apenas_url_e_tipo_minimo(): void
    {
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reference-contents.store', $profile),
            ['url' => 'https://instagram.com/reel/minimo', 'status' => ReferenceContentStatus::Active->value]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('reference_contents', [
            'reference_profile_id' => $profile->id,
            'url' => 'https://instagram.com/reel/minimo',
        ]);
    }

    public function test_create_completo(): void
    {
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create();

        $this->actingAs($user)->post(route('reference-contents.store', $profile), $this->validData())
            ->assertRedirect();

        $this->assertDatabaseHas('reference_contents', [
            'reference_profile_id' => $profile->id,
            'observed_hook' => 'Curiosity opening',
            'duration_seconds' => 45,
        ]);
    }

    public function test_url_invalida_e_duration_negativa_rejeitadas(): void
    {
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reference-contents.store', $profile),
            $this->validData(['url' => 'nao-e-url', 'duration_seconds' => -5])
        );

        $response->assertSessionHasErrors(['url', 'duration_seconds']);
        $this->assertDatabaseCount('reference_contents', 0);
    }

    public function test_update(): void
    {
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create();
        $content = ReferenceContent::factory()->for($profile, 'profile')->create(['observed_hook' => 'Antigo']);

        $this->actingAs($user)->put(
            route('reference-contents.update', [$profile, $content]),
            $this->validData(['observed_hook' => 'Visual problem in first second'])
        )->assertRedirect();

        $this->assertDatabaseHas('reference_contents', [
            'id' => $content->id,
            'observed_hook' => 'Visual problem in first second',
        ]);
    }

    public function test_conteudo_pertence_ao_perfil_e_cross_profile_404(): void
    {
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create();
        $other = ReferenceProfile::factory()->create();
        $content = ReferenceContent::factory()->for($other, 'profile')->create();

        $this->actingAs($user)->put(
            route('reference-contents.update', [$profile, $content]),
            $this->validData()
        )->assertNotFound();
    }

    public function test_matriz_archived(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $profile = ReferenceProfile::factory()->create();
        $active = ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Active]);
        $archived = ReferenceContent::factory()->for($profile, 'profile')->create(['status' => ReferenceContentStatus::Archived]);

        $this->actingAs($operator)->put(
            route('reference-contents.update', [$profile, $active]),
            $this->validData(['status' => ReferenceContentStatus::Archived->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('reference-contents.update', [$profile, $archived]),
            $this->validData(['status' => ReferenceContentStatus::Active->value])
        )->assertForbidden();

        $this->actingAs($admin)->put(
            route('reference-contents.update', [$profile, $active]),
            $this->validData(['status' => ReferenceContentStatus::Archived->value])
        )->assertRedirect();

        $this->assertTrue($active->fresh()->isArchived());
    }
}
