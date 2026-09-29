<?php

namespace Tests\Feature\CreativeStudio;

use App\Enums\AvatarStatus;
use App\Enums\UserRole;
use App\Models\Avatar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Emma',
            'apparent_age' => '27',
            'gender_presentation' => 'Female',
            'ethnicity_description' => null,
            'hair' => 'Brunette, medium length',
            'eyes' => 'Brown',
            'skin' => 'Natural warm complexion',
            'body_description' => null,
            'default_clothing' => 'Casual neutral outfits',
            'visual_style' => 'Natural UGC creator',
            'preferred_scenarios' => 'Bedroom, bathroom, vanity, natural daylight',
            'voice_description' => 'Young American female, friendly, relaxed',
            'language' => 'en-US',
            'market' => 'US',
            'reference_notes' => 'Use approved Emma reference images in future generation.',
            'status' => AvatarStatus::Active->value,
            'notes' => null,
        ], $overrides);
    }

    public function test_guest_nao_acessa_avatares(): void
    {
        $this->get('/avatars')->assertRedirect('/login');
        $this->post('/avatars', $this->validData())->assertRedirect('/login');
    }

    public function test_usuario_autenticado_acessa_lista(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get('/avatars')
            ->assertOk()
            ->assertSee('Avatares')
            ->assertSee('Nenhum avatar cadastrado', false);
    }

    public function test_avatar_pode_ser_criado(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/avatars', $this->validData());

        $avatar = Avatar::where('name', 'Emma')->firstOrFail();
        $response->assertRedirect(route('avatars.show', $avatar));
        $this->assertDatabaseHas('avatars', ['name' => 'Emma', 'apparent_age' => '27']);
    }

    public function test_dados_invalidos_sao_rejeitados(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/avatars', $this->validData([
            'name' => '',
            'language' => 'xx',
            'market' => 'XX',
            'status' => 'invalido',
        ]));

        $response->assertSessionHasErrors(['name', 'language', 'market', 'status']);
        $this->assertDatabaseCount('avatars', 0);
    }

    public function test_avatar_pode_ser_atualizado(): void
    {
        $user = User::factory()->create();
        $avatar = Avatar::factory()->create(['visual_style' => 'Antigo']);

        $response = $this->actingAs($user)->put(
            route('avatars.update', $avatar),
            $this->validData(['visual_style' => 'Natural UGC creator'])
        );

        $response->assertRedirect(route('avatars.show', $avatar));
        $this->assertDatabaseHas('avatars', ['id' => $avatar->id, 'visual_style' => 'Natural UGC creator']);
    }

    public function test_matriz_archived_equivalente(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $active = Avatar::factory()->create(['status' => AvatarStatus::Active]);
        $archived = Avatar::factory()->create(['status' => AvatarStatus::Archived]);

        $this->actingAs($operator)->put(
            route('avatars.update', $active),
            $this->validData(['status' => AvatarStatus::Archived->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('avatars.update', $archived),
            $this->validData(['status' => AvatarStatus::Active->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('avatars.update', $active),
            $this->validData(['status' => AvatarStatus::Paused->value])
        )->assertRedirect();

        $this->actingAs($admin)->put(
            route('avatars.update', $active),
            $this->validData(['status' => AvatarStatus::Archived->value])
        )->assertRedirect();

        $this->actingAs($admin)->put(
            route('avatars.update', $archived),
            $this->validData(['status' => AvatarStatus::Paused->value])
        )->assertRedirect();

        $this->assertTrue($active->fresh()->isArchived());
        $this->assertSame(AvatarStatus::Paused, $archived->fresh()->status);
    }
}
