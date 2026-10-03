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
use App\Models\ImageGenerationRequest;
use App\Models\MediaAsset;
use App\Models\Persona;
use App\Models\Product;
use App\Models\User;
use App\Services\AvatarReferenceService;
use App\Services\ImageGenerationService;
use App\Services\VisualPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

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

        $this->get(route('avatars.reference.create', $avatar))->assertRedirect('/login');
        $this->post(route('avatars.reference.store', $avatar))->assertRedirect('/login');
        $this->delete(route('avatars.reference.destroy', $avatar))->assertRedirect('/login');
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

        $this->assertNotNull($avatar->fresh()->reference_media_asset_id);
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
        $this->assertSame($asset->id, $avatar->fresh()->reference_media_asset_id);
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
        $this->assertNull($avatar->fresh()->reference_media_asset_id);
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

    // ---- replace / remove ----

    public function test_substituir_remove_anterior_exclusivo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $old = $service->attach($avatar, $this->upload($this->png600()), $user->id);
        $oldPath = $old->path;

        $new = $service->attach($avatar, $this->upload($this->jpg600(), 'nova.jpg'), $user->id);

        $this->assertSame($new->id, $avatar->fresh()->reference_media_asset_id);
        $this->assertDatabaseMissing('media_assets', ['id' => $old->id]);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($new->path);
    }

    public function test_substituir_preserva_anterior_compartilhado(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $old = $service->attach($avatar, $this->upload($this->png600()), $user->id);
        ImageGenerationRequest::factory()->create(['reference_media_asset_id' => $old->id]);

        $new = $service->attach($avatar, $this->upload($this->jpg600(), 'nova.jpg'), $user->id);

        $this->assertSame($new->id, $avatar->fresh()->reference_media_asset_id);
        $this->assertDatabaseHas('media_assets', ['id' => $old->id]);
        Storage::disk('public')->assertExists($old->path);
    }

    public function test_remover_referencia(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $asset = $service->attach($avatar, $this->upload($this->png600()), $user->id);

        $this->actingAs($user)->delete(route('avatars.reference.destroy', $avatar))
            ->assertRedirect(route('avatars.show', $avatar));

        $this->assertNull($avatar->fresh()->reference_media_asset_id);
        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        Storage::disk('public')->assertMissing($asset->path);
        $this->assertDatabaseHas('avatars', ['id' => $avatar->id]);
    }

    public function test_remover_preserva_compartilhado(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $service = app(AvatarReferenceService::class);

        $asset = $service->attach($avatar, $this->upload($this->png600()), $user->id);
        ImageGenerationRequest::factory()->create(['reference_media_asset_id' => $asset->id]);

        $service->detach($avatar);

        $this->assertNull($avatar->fresh()->reference_media_asset_id);
        $this->assertDatabaseHas('media_assets', ['id' => $asset->id]);
    }

    // ---- snapshot da geração ----

    public function test_request_sem_referencia_tem_snapshot_null(): void
    {
        $script = $this->context();

        $request = app(ImageGenerationService::class)->createRequest(
            'Vertical photorealistic UGC scene with neutral daylight.',
            [
                'content_script_id' => $script->id,
                'reference_media_asset_id' => $script->avatar->reference_media_asset_id,
            ],
            null,
        );

        $this->assertNull($request->reference_media_asset_id);
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
                'reference_media_asset_id' => $script->avatar->reference_media_asset_id,
            ],
            null,
        );

        $this->assertSame($ref->id, $request->reference_media_asset_id);

        // Trocar a referência depois não altera o snapshot.
        $new = app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->jpg600(), 'n.jpg'));
        $this->assertSame($ref->id, $request->fresh()->reference_media_asset_id);
        $this->assertSame($new->id, $avatar->fresh()->reference_media_asset_id);
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
            ['content_script_id' => $script->id, 'reference_media_asset_id' => $ref->id],
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
        $this->assertSame($ref->id, $log->metadata['reference_media_asset_id']);
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
            ['content_script_id' => $script->id, 'reference_media_asset_id' => $ref->id],
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
            ['content_script_id' => $script->id, 'reference_media_asset_id' => $ref->id],
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
            $script, $script->product, $script->blueprint, $script->persona, $script->avatar,
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
            ->assertSee('Nenhuma imagem de referência', false)
            ->assertSee('Adicionar referência', false);
    }

    public function test_avatar_show_com_referencia(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $avatar = $this->avatar();
        $asset = app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->png600()));
        $asset->update(['size_bytes' => 809158]);

        $this->withoutVite()->actingAs($user)->get(route('avatars.show', $avatar->fresh()))
            ->assertOk()
            ->assertSee('Abrir imagem', false)
            ->assertSee('Substituir', false)
            ->assertSee('Remover referência', false)
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
            ->assertSee('Imagem de referência será usada', false);

        $this->withoutVite()->actingAs($user)->get(route('scripts.images.create', $withoutRef))
            ->assertOk()
            ->assertSee('usará apenas o Visual DNA', false);
    }
}
