<?php

namespace Tests\Feature\SocialAccounts;

use App\Enums\SocialAccountStatus;
use App\Enums\UserRole;
use App\Models\Avatar;
use App\Models\Persona;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialAccountTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Beauty Finds US',
            'platform' => 'instagram',
            'username' => 'beautyfindsus',
            'profile_url' => 'https://instagram.com/beautyfindsus',
            'language' => 'en-US',
            'market' => 'US',
            'niche' => 'Beauty / Skincare',
            'audience' => 'Women 20–40',
            'tone' => 'Natural, casual, friendly',
            'content_style' => 'UGC, product discovery, problem/solution',
            'default_cta' => 'Check the link in bio',
            'posting_frequency' => '2 Reels/day',
            'status' => SocialAccountStatus::Active->value,
            'notes' => null,
        ], $overrides);
    }

    public function test_guest_nao_acessa_contas(): void
    {
        $this->get('/social-accounts')->assertRedirect('/login');
        $this->post('/social-accounts', $this->validData())->assertRedirect('/login');
    }

    public function test_usuario_autenticado_acessa_lista(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get('/social-accounts')
            ->assertOk()
            ->assertSee('Contas')
            ->assertSee('Nenhuma conta cadastrada', false);
    }

    public function test_conta_aceita_persona_e_avatar_validos(): void
    {
        $user = User::factory()->create();
        $persona = Persona::factory()->create();
        $avatar = Avatar::factory()->create();

        $response = $this->actingAs($user)->post('/social-accounts', $this->validData([
            'default_persona_id' => $persona->id,
            'default_avatar_id' => $avatar->id,
        ]));

        $account = SocialAccount::where('username', 'beautyfindsus')->firstOrFail();
        $response->assertRedirect(route('social-accounts.show', $account));
        $this->assertTrue($account->defaultPersona->is($persona));
        $this->assertTrue($account->defaultAvatar->is($avatar));
    }

    public function test_ids_inexistentes_sao_rejeitados(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/social-accounts', $this->validData([
            'default_persona_id' => 999,
            'default_avatar_id' => 999,
        ]));

        $response->assertSessionHasErrors(['default_persona_id', 'default_avatar_id']);
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_conta_funciona_sem_persona_avatar_e_show_apresenta_quando_definidos(): void
    {
        $user = User::factory()->create();
        $persona = Persona::factory()->create(['name' => 'Emma US Beauty']);
        $avatar = Avatar::factory()->create(['name' => 'Emma']);

        $plain = SocialAccount::factory()->create();
        $linked = SocialAccount::factory()->create([
            'default_persona_id' => $persona->id,
            'default_avatar_id' => $avatar->id,
        ]);

        $this->assertNull($plain->default_persona_id);

        $response = $this->withoutVite()->actingAs($user)->get(route('social-accounts.show', $linked))->assertOk();
        $response->assertSee('Emma US Beauty');
        $response->assertSee('Persona padrão', false);

        $responsePlain = $this->withoutVite()->actingAs($user)->get(route('social-accounts.show', $plain))->assertOk();
        $responsePlain->assertSee('Não definida', false);
        $responsePlain->assertSee('Não definido', false);
    }

    public function test_username_e_renderizado_com_valor_real(): void
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->create(['username' => 'beautyfindsus']);

        $index = $this->withoutVite()->actingAs($user)->get('/social-accounts')->assertOk();
        $index->assertSee('@beautyfindsus', false);
        $index->assertDontSee('{{ $account->username }}', false);

        $show = $this->withoutVite()->actingAs($user)->get(route('social-accounts.show', $account))->assertOk();
        $show->assertSee('@beautyfindsus', false);
        $show->assertDontSee('{{ $account->username }}', false);
    }

    public function test_conta_pode_ser_criada(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/social-accounts', $this->validData());

        $account = SocialAccount::where('username', 'beautyfindsus')->firstOrFail();
        $response->assertRedirect(route('social-accounts.show', $account));
        $this->assertDatabaseHas('social_accounts', [
            'username' => 'beautyfindsus',
            'platform' => 'instagram',
            'market' => 'US',
        ]);
    }

    public function test_username_sem_arroba_e_normalizado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/social-accounts', $this->validData(['username' => '@beautyfindsus2']));

        $this->assertDatabaseHas('social_accounts', ['username' => 'beautyfindsus2']);
    }

    public function test_dados_invalidos_sao_rejeitados(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/social-accounts', $this->validData([
            'name' => '',
            'platform' => 'orkut',
            'profile_url' => 'nao-e-url',
            'language' => 'xx',
            'market' => 'XX',
            'status' => 'invalido',
        ]));

        $response->assertSessionHasErrors(['name', 'platform', 'profile_url', 'language', 'market', 'status']);
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_conta_pode_ser_atualizada(): void
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->create(['niche' => 'Antigo']);

        $response = $this->actingAs($user)->put(
            route('social-accounts.update', $account),
            $this->validData(['username' => $account->username, 'niche' => 'Beauty / Skincare'])
        );

        $response->assertRedirect(route('social-accounts.show', $account));
        $this->assertDatabaseHas('social_accounts', ['id' => $account->id, 'niche' => 'Beauty / Skincare']);
    }

    public function test_username_pode_repetir_em_plataformas_diferentes(): void
    {
        $user = User::factory()->create();
        SocialAccount::factory()->create(['platform' => 'instagram', 'username' => 'achadinhos']);

        $this->actingAs($user)->post(
            '/social-accounts',
            $this->validData(['platform' => 'tiktok', 'username' => 'achadinhos'])
        )->assertRedirect();

        $this->assertSame(2, SocialAccount::where('username', 'achadinhos')->count());
    }

    public function test_duplicidade_platform_username_e_rejeitada(): void
    {
        $user = User::factory()->create();
        SocialAccount::factory()->create(['platform' => 'instagram', 'username' => 'achadinhos']);

        $this->actingAs($user)->post(
            '/social-accounts',
            $this->validData(['platform' => 'instagram', 'username' => 'achadinhos'])
        )->assertSessionHasErrors('username');
    }

    public function test_operator_pode_alternar_entre_ativa_e_pausada(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $active = SocialAccount::factory()->create(['status' => SocialAccountStatus::Active]);
        $paused = SocialAccount::factory()->create(['status' => SocialAccountStatus::Paused]);

        $this->actingAs($operator)->put(
            route('social-accounts.update', $active),
            $this->validData(['username' => $active->username, 'status' => SocialAccountStatus::Paused->value])
        )->assertRedirect();

        $this->actingAs($operator)->put(
            route('social-accounts.update', $paused),
            $this->validData(['username' => $paused->username, 'status' => SocialAccountStatus::Active->value])
        )->assertRedirect();

        $this->assertSame(SocialAccountStatus::Paused, $active->fresh()->status);
        $this->assertSame(SocialAccountStatus::Active, $paused->fresh()->status);
    }

    public function test_operator_nao_pode_arquivar_nem_desarquivar(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $active = SocialAccount::factory()->create(['status' => SocialAccountStatus::Active]);
        $archived = SocialAccount::factory()->create(['status' => SocialAccountStatus::Archived]);

        $this->actingAs($operator)->put(
            route('social-accounts.update', $active),
            $this->validData(['username' => $active->username, 'status' => SocialAccountStatus::Archived->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('social-accounts.update', $archived),
            $this->validData(['username' => $archived->username, 'status' => SocialAccountStatus::Active->value])
        )->assertForbidden();

        $this->actingAs($operator)->put(
            route('social-accounts.update', $archived),
            $this->validData(['username' => $archived->username, 'status' => SocialAccountStatus::Paused->value])
        )->assertForbidden();

        $this->assertSame(SocialAccountStatus::Active, $active->fresh()->status);
        $this->assertTrue($archived->fresh()->isArchived());
    }

    public function test_admin_pode_arquivar_e_reativar(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $account = SocialAccount::factory()->create(['status' => SocialAccountStatus::Active]);

        $this->actingAs($admin)->put(
            route('social-accounts.update', $account),
            $this->validData(['username' => $account->username, 'status' => SocialAccountStatus::Archived->value])
        )->assertRedirect();
        $this->assertTrue($account->fresh()->isArchived());

        $this->actingAs($admin)->put(
            route('social-accounts.update', $account),
            $this->validData(['username' => $account->username, 'status' => SocialAccountStatus::Active->value])
        )->assertRedirect();
        $this->assertSame(SocialAccountStatus::Active, $account->fresh()->status);
    }
}
