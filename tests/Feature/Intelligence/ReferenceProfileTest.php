<?php

namespace Tests\Feature\Intelligence;

use App\Enums\ReferenceProfileStatus;
use App\Enums\UserRole;
use App\Models\ReferenceProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceProfileTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Beauty Creator US',
            'platform' => 'instagram',
            'username' => 'beautycreator',
            'profile_url' => 'https://instagram.com/beautycreator',
            'language' => 'en-US',
            'market' => 'US',
            'niche' => 'Beauty / Skincare',
            'reason' => 'Strong short-form UGC structure and product demonstrations.',
            'status' => ReferenceProfileStatus::Active->value,
            'notes' => null,
        ], $overrides);
    }

    public function test_guest_bloqueado(): void
    {
        $this->get('/references')->assertRedirect('/login');
        $this->post('/references', $this->validData())->assertRedirect('/login');
    }

    public function test_index_autenticado(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get('/references')
            ->assertOk()
            ->assertSee('Referências')
            ->assertSee('Nenhuma referência cadastrada', false);
    }

    public function test_create(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/references', $this->validData());

        $profile = ReferenceProfile::where('username', 'beautycreator')->firstOrFail();
        $response->assertRedirect(route('references.show', $profile));
        $this->assertDatabaseHas('reference_profiles', ['platform' => 'instagram', 'market' => 'US']);
    }

    public function test_validacao_url_e_platform(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/references', $this->validData([
            'name' => '',
            'platform' => 'orkut',
            'profile_url' => 'nao-e-url',
            'language' => 'xx',
            'market' => 'XX',
        ]));

        $response->assertSessionHasErrors(['name', 'platform', 'profile_url', 'language', 'market']);
        $this->assertDatabaseCount('reference_profiles', 0);
    }

    public function test_update(): void
    {
        $user = User::factory()->create();
        $profile = ReferenceProfile::factory()->create(['niche' => 'Antigo']);

        $response = $this->actingAs($user)->put(
            route('references.update', $profile),
            $this->validData(['username' => $profile->username, 'niche' => 'Beauty / Skincare'])
        );

        $response->assertRedirect(route('references.show', $profile));
        $this->assertDatabaseHas('reference_profiles', ['id' => $profile->id, 'niche' => 'Beauty / Skincare']);
    }

    public function test_matriz_archived(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $active = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Active]);
        $archived = ReferenceProfile::factory()->create(['status' => ReferenceProfileStatus::Archived]);

        $this->actingAs($operator)->put(
            route('references.update', $active),
            $this->validData(['status' => ReferenceProfileStatus::Archived->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('references.update', $archived),
            $this->validData(['status' => ReferenceProfileStatus::Active->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('references.update', $active),
            $this->validData(['status' => ReferenceProfileStatus::Paused->value])
        )->assertRedirect();

        $this->actingAs($admin)->put(
            route('references.update', $active),
            $this->validData(['status' => ReferenceProfileStatus::Archived->value])
        )->assertRedirect();

        $this->actingAs($admin)->put(
            route('references.update', $archived),
            $this->validData(['status' => ReferenceProfileStatus::Active->value])
        )->assertRedirect();

        $this->assertTrue($active->fresh()->isArchived());
        $this->assertSame(ReferenceProfileStatus::Active, $archived->fresh()->status);
    }
}
