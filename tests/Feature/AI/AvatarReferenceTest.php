<?php

namespace Tests\Feature\AI;

use App\Enums\ContentScriptStatus;
use App\Enums\ImageGenerationRequestStatus;
use App\Enums\MediaAssetSource;
use App\Enums\UserRole;
use App\Jobs\GenerateImageJob;
use App\Models\AiGeneration;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Services\AvatarReferenceService;
use App\Services\ImageGenerationService;
use App\Services\VisualPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fundação de referência do Avatar (Sprint 5.5.2), agora sobre a pivot
 * multi-reference da 5.5.3: upload validado, segurança, snapshot via pivot,
 * Job multi, prompt e UI. Multi-ref avançado em AvatarMultiReferenceTest.
 */
class AvatarReferenceTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /**
     * PNG 600×600 para validação (IHDR forjado; getimagesize aprova.
     * Sem GD no ambiente — nunca renderizado, só validação binária).
     */
    private function png600(): string
    {
        return substr_replace(
            (string) base64_decode(self::PNG_1X1),
            pack('N', 600).pack('N', 600),
            16,
            8
        );
    }

    /**
     * JPEG 600×600 mínimo válido (SOI + SOF0 + EOI).
     */
    private function jpg600(): string
    {
        return (string) hex2bin('ffd8ffc0000b080258025801011100ffd9');
    }

    private function upload(string $binary, string $name = 'foto.png', ?string $mime = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ref').'_'.$name;
        file_put_contents($path, $binary);

        return new UploadedFile($path, $name, $mime, UPLOAD_ERR_OK, true);
    }

    private function avatar(): Avatar
    {
        return Avatar::factory()->create(['language' => 'en-US', 'market' => 'US']);
    }

    private function context(?Avatar $avatar = null): ContentScript
    {
        $product = Product::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $blueprint = ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $persona = Persona::factory()->create(['language' => 'en-US', 'market' => 'US']);

        return ContentScript::factory()->create([
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => ($avatar ?? $this->avatar())->id,
            'status' => ContentScriptStatus::Ready,
        ]);
    }

    private function enableImage(): void
    {
        config()->set('ai.google.image.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    private function fakeImage(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-ref-1',
            'steps' => [
                ['type' => 'model_output', 'content' => [['type' => 'image', 'data' => self::PNG_1X1, 'mime_type' => 'image/png']]],
            ],
        ], 200)]);
    }

    // ---- upload ----

    public function test_guest_bloqueado(): void
    {
        $avatar = $this->avatar();
        $asset = MediaAsset::factory()->create();

        $this->get(route('avatars.reference.create', $avatar))->assertRedirect('/login');
        $this->post(route('avatars.reference.store', $avatar))->assertRedirect('/login');
        $this->delete(route('avatars.references.destroy', [$avatar, $asset]))->assertRedirect('/login');
    }

    public function test_operator_pode_enviar(): void
    {
        Storage::fake('public');
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $avatar = $this->avatar();

        $this->actingAs($operator)
            ->post(route('avatars.reference.store', $avatar), [
                'image' => $this->upload($this->png600(), 'foto.png', 'image/png'),
            ])
            ->assertRedirect(route('avatars.show', $avatar));

        $this->assertSame(1, $avatar->fresh()->referenceImages()->count());
    }

    public function test_upload_png_valido(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();

        $this->actingAs($user)->post(route('avatars.reference.store', $avatar), [
            'image' => $this->upload($this->png600(), 'foto.png', 'image/png'),
        ])->assertRedirect(route('avatars.show', $avatar));

        $asset = MediaAsset::firstOrFail();
        $this->assertSame(MediaAssetSource::Uploaded, $asset->source);
        $this->assertSame('image/png', $asset->mime_type);
        $this->assertSame(600, $asset->width);
        $this->assertSame(600, $asset->height);
        $this->assertTrue($avatar->fresh()->referenceImages()->whereKey($asset->id)->exists());
        $this->assertStringStartsWith('avatars/references/', $asset->path);
        $this->assertMatchesRegularExpression('#/[\w-]{36}\.png$#', $asset->path);
        $this->assertStringNotContainsString('foto', $asset->path);
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_upload_jpeg_valido(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();

        $this->actingAs($user)->post(route('avatars.reference.store', $avatar), [
            'image' => $this->upload($this->jpg600(), 'foto.jpg', 'image/jpeg'),
        ])->assertRedirect(route('avatars.show', $avatar));

        $asset = MediaAsset::firstOrFail();
        $this->assertSame('image/jpeg', $asset->mime_type);
        $this->assertStringEndsWith('.jpg', $asset->path);
    }

    public function test_arquivo_nao_imagem_rejeitado(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();

        $this->actingAs($user)->post(route('avatars.reference.store', $avatar), [
            'image' => $this->upload('isto nao e imagem', 'foto.png', 'image/png'),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('media_assets', 0);
        $this->assertSame(0, $avatar->fresh()->referenceImages()->count());
    }

    public function test_extensao_falsa_rejeitada(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();

        $this->actingAs($user)->post(route('avatars.reference.store', $avatar), [
            'image' => $this->upload('texto plano', 'foto.jpg', 'text/plain'),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_arquivo_grande_rejeitado(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $big = $this->jpg600().str_repeat("\0", 11 * 1024 * 1024);

        $this->actingAs($user)->post(route('avatars.reference.store', $avatar), [
            'image' => $this->upload($big, 'grande.jpg', 'image/jpeg'),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_imagem_pequena_rejeitada(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();

        $this->actingAs($user)->post(route('avatars.reference.store', $avatar), [
            'image' => $this->upload((string) base64_decode(self::PNG_1X1), 'tiny.png', 'image/png'),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_sem_base64_ou_absoluto_no_banco(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $binary = $this->png600();

        $this->actingAs($user)->post(route('avatars.reference.store', $avatar), [
            'image' => $this->upload($binary, 'foto.png', 'image/png'),
        ])->assertRedirect();

        $dump = json_encode([
            MediaAsset::firstOrFail()->toArray(),
            $avatar->fresh()->toArray(),
        ]);
        $this->assertStringNotContainsString(base64_encode($binary), (string) $dump);
        $this->assertStringNotContainsString(sys_get_temp_dir(), (string) $dump);
        $this->assertStringNotContainsString('foto.png', (string) $dump);
    }

    // ---- remove ----

    public function test_remover_referencia(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $asset = $service->attach($avatar, $this->upload($this->png600()), $user->id);

        $this->actingAs($user)
            ->delete(route('avatars.references.destroy', [$avatar, $asset]))
            ->assertRedirect(route('avatars.show', $avatar));

        $this->assertSame(0, $avatar->fresh()->referenceImages()->count());
        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        Storage::disk('public')->assertMissing($asset->path);
        $this->assertDatabaseHas('avatars', ['id' => $avatar->id]);
    }

    public function test_remover_asset_de_outro_avatar_rejeitado(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $other = $this->avatar();
        $asset = app(AvatarReferenceService::class)->attach($other, $this->upload($this->png600()), $user->id);

        $this->actingAs($user)
            ->delete(route('avatars.references.destroy', [$this->avatar(), $asset]))
            ->assertNotFound();

        $this->assertDatabaseHas('media_assets', ['id' => $asset->id]);
    }

    // ---- snapshot da geração ----

    public function test_request_sem_referencia_tem_snapshot_vazio(): void
    {
        $script = $this->context();

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            ['content_script_id' => $script->id],
            null,
        );

        $this->assertSame(0, $request->referenceImages()->count());
    }

    public function test_request_com_referencia_tem_snapshot(): void
    {
        Storage::fake('public');
        $avatar = $this->avatar();
        $ref = app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->png600()));
        $script = $this->context($avatar);

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            [
                'content_script_id' => $script->id,
                'reference_media_asset_ids' => $script->avatar->referenceImages()->pluck('media_assets.id')->all(),
            ],
            null,
        );

        $this->assertSame([$ref->id], $request->referenceImages()->pluck('media_assets.id')->all());

        // Remover do Avatar depois não altera o snapshot.
        app(AvatarReferenceService::class)->remove($avatar, $ref);
        $this->assertSame([$ref->id], $request->fresh()->referenceImages()->pluck('media_assets.id')->all());
    }

    public function test_job_usa_referencia_do_request(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $avatar = $this->avatar();
        $binary = $this->png600();
        $ref = app(AvatarReferenceService::class)->attach($avatar, $this->upload($binary));
        $script = $this->context($avatar);

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            ['content_script_id' => $script->id, 'reference_media_asset_ids' => [$ref->id]],
            null,
        );

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $this->assertSame(ImageGenerationRequestStatus::Success, $request->fresh()->status);

        Http::assertSent(function ($http) use ($binary) {
            $input = $http->data()['input'] ?? null;

            return is_array($input)
                && ($input[0]['type'] ?? null) === 'text'
                && ($input[1]['type'] ?? null) === 'image'
                && ($input[1]['mime_type'] ?? null) === 'image/png'
                && base64_decode($input[1]['data'] ?? '', true) === $binary;
        });

        $log = AiGeneration::firstWhere('operation', 'image_generation');
        $this->assertTrue((bool) $log->metadata['reference_used']);
        $this->assertSame(1, $log->metadata['reference_count']);
        $this->assertSame([$ref->id], $log->metadata['reference_media_asset_ids']);
        $dump = json_encode([$log->metadata, $log->error_code]);
        $this->assertStringNotContainsString(base64_encode($binary), (string) $dump);
    }

    public function test_sem_referencia_mantem_payload_textual(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $script = $this->context();

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            ['content_script_id' => $script->id],
            null,
        );

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $this->assertSame(ImageGenerationRequestStatus::Success, $request->fresh()->status);

        Http::assertSent(function ($http) {
            return is_string($http->data()['input'] ?? null);
        });
    }

    // ---- falhas ----

    public function test_referencia_ausente_falha_sem_provider(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $avatar = $this->avatar();
        $ref = app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->png600()));
        $script = $this->context($avatar);

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            ['content_script_id' => $script->id, 'reference_media_asset_ids' => [$ref->id]],
            null,
        );

        Storage::disk('public')->delete($ref->path);

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $request = $request->fresh();
        $this->assertSame(ImageGenerationRequestStatus::Failed, $request->status);
        $this->assertSame('reference_missing', $request->error_code);
        $this->assertNull($request->media_asset_id);
        Http::assertNothingSent();
    }

    public function test_referencia_invalida_falha_sem_provider(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $avatar = $this->avatar();
        $ref = app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->png600()));
        Storage::disk('public')->put($ref->path, 'conteudo corrompido');
        $script = $this->context($avatar);

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            ['content_script_id' => $script->id, 'reference_media_asset_ids' => [$ref->id]],
            null,
        );

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $request = $request->fresh();
        $this->assertSame(ImageGenerationRequestStatus::Failed, $request->status);
        $this->assertSame('reference_invalid', $request->error_code);
        Http::assertNothingSent();
    }

    // ---- prompt ----

    public function test_prompt_inclui_instrucao_com_referencia(): void
    {
        Storage::fake('public');
        $avatar = $this->avatar();
        app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->png600()));
        $script = $this->context($avatar->fresh());

        $prompt = app(VisualPromptBuilder::class)->build(
            $script, $script->product, $script->blueprint, $script->persona, $script->avatar, 1,
        );

        $this->assertStringContainsString(
            'Use the provided reference image to preserve the Avatar',
            $prompt
        );
    }

    public function test_prompt_sem_referencia_sem_instrucao(): void
    {
        $script = $this->context();

        $prompt = app(VisualPromptBuilder::class)->build(
            $script, $script->product, $script->blueprint, $script->persona, $script->avatar,
        );

        $this->assertStringNotContainsString('reference image', $prompt);
    }

    // ---- UI ----

    public function test_avatar_show_empty_state(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()->actingAs($user)->get(route('avatars.show', $this->avatar()))
            ->assertOk()
            ->assertSee('Nenhuma referência visual', false)
            ->assertSee('Adicionar referência', false);
    }

    public function test_avatar_show_com_referencia(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $asset = app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->png600()));
        $asset->update(['size_bytes' => 809158]);
        app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'));

        $this->withoutVite()->actingAs($user)->get(route('avatars.show', $avatar->fresh()))
            ->assertOk()
            ->assertSee('Abrir imagem', false)
            ->assertSee('Definir como principal', false)
            ->assertSee('Remover', false)
            ->assertSee('790.2 KB', false)
            ->assertDontSee('809158', false);
    }

    public function test_avatar_show_sem_tamanho_nao_quebra(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $asset = app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->png600()));
        $asset->update(['size_bytes' => null]);

        $this->withoutVite()->actingAs($user)->get(route('avatars.show', $avatar->fresh()))
            ->assertOk()
            ->assertSee('image/png', false);
    }

    public function test_script_create_indica_referencia(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $withRef = $this->context();
        app(AvatarReferenceService::class)->attach($withRef->avatar, $this->upload($this->png600()));
        $withoutRef = $this->context();

        $this->withoutVite()->actingAs($user)->get(route('scripts.images.create', $withRef->fresh()))
            ->assertOk()
            ->assertSee('Referências visuais', false);

        $this->withoutVite()->actingAs($user)->get(route('scripts.images.create', $withoutRef))
            ->assertOk()
            ->assertSee('usará apenas o Visual DNA', false);
    }

    public function test_pivot_multi_reference(): void
    {
        Storage::fake('public');
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $first = $service->attach($avatar, $this->upload($this->png600()));
        $second = $service->attach($avatar, $this->upload($this->jpg600(), 'b.jpg'));

        $this->assertSame(1, (int) DB::table('avatar_reference_media_assets')
            ->where('avatar_id', $avatar->id)->where('is_primary', true)->count());
        $this->assertSame($first->id, $avatar->primaryReferenceImage()->id);
        $this->assertSame(
            [$first->id, $second->id],
            $avatar->fresh()->referenceImages()->pluck('media_assets.id')->all()
        );
    }
}
