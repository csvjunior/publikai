<?php

namespace Tests\Feature\AI;

use App\Enums\ContentScriptStatus;
use App\Enums\ImageGenerationRequestStatus;
use App\Enums\MediaAssetStatus;
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
use App\Services\ImageEditPromptBuilder;
use App\Services\ImageGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Edição/variação controlada de imagem (Sprint 5.5.4): source snapshot,
 * original imutável, provider source+refs, operation image_edit, output
 * derivado, pivot, primary, segurança e UI.
 */
class ImageEditTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private function png600(): string
    {
        return substr_replace(
            (string) base64_decode(self::PNG_1X1),
            pack('N', 600).pack('N', 600),
            16,
            8
        );
    }

    private function upload(string $binary, string $name = 'foto.png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'edit').'_'.$name;
        file_put_contents($path, $binary);

        return new UploadedFile($path, $name, null, UPLOAD_ERR_OK, true);
    }

    private function context(?Avatar $avatar = null): ContentScript
    {
        return ContentScript::factory()->create([
            'product_id' => Product::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'content_blueprint_id' => ContentBlueprint::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'persona_id' => Persona::factory()->create(['language' => 'en-US', 'market' => 'US'])->id,
            'avatar_id' => ($avatar ?? Avatar::factory()->create(['language' => 'en-US', 'market' => 'US']))->id,
            'status' => ContentScriptStatus::Ready,
        ]);
    }

    private function scriptAsset(ContentScript $script, array $overrides = []): MediaAsset
    {
        $asset = MediaAsset::factory()->create($overrides);
        Storage::disk('public')->put($asset->path, $this->png600());
        $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => false]);

        return $asset;
    }

    private function enableImage(): void
    {
        config()->set('ai.google.image.enabled', true);
        config()->set('ai.google.auth_key', 'test-auth-key');
    }

    private function fakeImage(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'id' => 'interaction-edit-1',
            'steps' => [
                ['type' => 'model_output', 'content' => [['type' => 'image', 'data' => self::PNG_1X1, 'mime_type' => 'image/png']]],
            ],
        ], 200)]);
    }

    private function validPost(array $overrides = []): array
    {
        return array_merge([
            'change' => 'Move the character to a modern living room with morning light.',
            'aspect_ratio' => '9:16',
            'image_size' => '1K',
            'mime_type' => 'image/png',
            'purpose' => 'scene',
        ], $overrides);
    }

    // ---- auth / source ----

    public function test_guest_bloqueado(): void
    {
        $script = $this->context();
        $asset = MediaAsset::factory()->create();
        $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => false]);

        $this->get(route('scripts.images.edit', [$script, $asset]))->assertRedirect('/login');
        $this->post(route('scripts.images.edit.store', [$script, $asset]))->assertRedirect('/login');
    }

    public function test_source_de_outro_script_rejeitada(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $foreign = MediaAsset::factory()->create();

        $this->actingAs($user)->get(route('scripts.images.edit', [$script, $foreign]))->assertNotFound();
        $this->actingAs($user)->post(route('scripts.images.edit.store', [$script, $foreign]), $this->validPost())->assertNotFound();
        $this->assertDatabaseCount('image_generation_requests', 0);
    }

    public function test_source_nao_pronta_rejeitada(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $asset = MediaAsset::factory()->create(['status' => MediaAssetStatus::Failed]);
        $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => false]);

        $this->actingAs($user)->post(route('scripts.images.edit.store', [$script, $asset]), $this->validPost())->assertStatus(422);
        $this->assertDatabaseCount('image_generation_requests', 0);
    }

    public function test_draft_nao_varia(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $script->update(['status' => ContentScriptStatus::Draft]);
        $asset = MediaAsset::factory()->create();
        $script->mediaAssets()->attach($asset->id, ['purpose' => 'scene', 'is_primary' => false]);

        $this->actingAs($user)->get(route('scripts.images.edit', [$script, $asset]))->assertForbidden();
    }

    public function test_change_curta_rejeitada(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $script = $this->context();
        $asset = $this->scriptAsset($script);

        $this->actingAs($user)->post(
            route('scripts.images.edit.store', [$script, $asset]),
            $this->validPost(['change' => 'muda'])
        )->assertSessionHasErrors('change');
    }

    // ---- fluxo ----

    public function test_get_edit_mostra_base_e_defaults(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $script = $this->context();
        $asset = $this->scriptAsset($script);

        $this->withoutVite()->actingAs($user)->get(route('scripts.images.edit', [$script, $asset]))
            ->assertOk()
            ->assertSee('Imagem base', false)
            ->assertSee('Alteração desejada', false)
            ->assertSee('Gerar variação', false);
    }

    public function test_post_cria_request_com_source_snapshot(): void
    {
        Storage::fake('public');
        $this->enableImage();
        Queue::fake();
        $user = User::factory()->create();
        $script = $this->context();
        $asset = $this->scriptAsset($script);

        $this->actingAs($user)->post(route('scripts.images.edit.store', [$script, $asset]), $this->validPost())
            ->assertRedirect(route('scripts.show', $script));

        $request = ImageGenerationRequest::firstOrFail();
        $this->assertSame($asset->id, $request->source_media_asset_id);
        $this->assertSame($script->id, $request->content_script_id);
        Queue::assertPushed(GenerateImageJob::class);
    }

    public function test_job_success_operation_edit_e_derivacao(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $script = $this->context();
        $source = $this->scriptAsset($script);
        $sourcePath = $source->path;

        $request = app(ImageGenerationService::class)->createRequest(
            'EDIT REQUEST - change the scene keeping the character.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $source->id],
            null,
        );

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $this->assertSame(ImageGenerationRequestStatus::Success, $request->fresh()->status);

        $output = MediaAsset::where('parent_media_asset_id', $source->id)->firstOrFail();
        $this->assertNotSame($output->id, $source->id);
        $this->assertNotSame($output->path, $sourcePath);

        // Original intacto: mesma linha, mesmo arquivo.
        $this->assertDatabaseHas('media_assets', ['id' => $source->id, 'path' => $sourcePath]);
        Storage::disk('public')->assertExists($sourcePath);

        // Pivot do novo output; original segue vinculado.
        $this->assertTrue($script->fresh()->mediaAssets()->whereKey($output->id)->exists());
        $this->assertTrue($script->fresh()->mediaAssets()->whereKey($source->id)->exists());

        $log = AiGeneration::firstWhere('operation', 'image_edit');
        $this->assertSame('success', $log->status->value);
        $this->assertSame($source->id, $log->metadata['source_media_asset_id']);
        $this->assertNull(AiGeneration::firstWhere('operation', 'image_generation'));
    }

    // ---- source + refs ----

    public function test_source_only_payload(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $script = $this->context();
        $source = $this->scriptAsset($script);
        $binary = $this->png600();
        Storage::disk('public')->put($source->path, $binary);

        $request = app(ImageGenerationService::class)->createRequest(
            'EDIT REQUEST - change the scene keeping the character.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $source->id],
            null,
        );

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $this->assertSame(ImageGenerationRequestStatus::Success, $request->fresh()->status);

        Http::assertSent(function ($http) use ($binary) {
            $input = $http->data()['input'] ?? null;

            return is_array($input)
                && count($input) === 2
                && ($input[0]['type'] ?? null) === 'text'
                && ($input[1]['type'] ?? null) === 'image'
                && base64_decode($input[1]['data'] ?? '', true) === $binary;
        });
    }

    public function test_source_mais_refs_ordem(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $avatar = Avatar::factory()->create(['language' => 'en-US', 'market' => 'US']);
        $ref = app(AvatarReferenceService::class)->attach($avatar, $this->upload($this->png600()));
        $script = $this->context($avatar);
        $source = $this->scriptAsset($script);
        $srcBinary = $this->png600();
        Storage::disk('public')->put($source->path, $srcBinary);

        $request = app(ImageGenerationService::class)->createRequest(
            'EDIT REQUEST - change the scene keeping the character.',
            [
                'content_script_id' => $script->id,
                'source_media_asset_id' => $source->id,
                'reference_media_asset_ids' => [$ref->id],
            ],
            null,
        );

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $this->assertSame(ImageGenerationRequestStatus::Success, $request->fresh()->status);

        Http::assertSent(function ($http) use ($srcBinary) {
            $input = $http->data()['input'] ?? null;

            return is_array($input)
                && count($input) === 3
                && ($input[0]['type'] ?? null) === 'text'
                && base64_decode($input[1]['data'] ?? '', true) === $srcBinary
                && ($input[1]['mime_type'] ?? null) === 'image/png'
                && ($input[2]['mime_type'] ?? null) === 'image/png';
        });

        $log = AiGeneration::firstWhere('operation', 'image_edit');
        $this->assertSame(1, $log->metadata['reference_count']);
    }

    public function test_source_missing_e_invalid(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $script = $this->context();

        $missing = $this->scriptAsset($script);
        Storage::disk('public')->delete($missing->path);
        $reqMissing = app(ImageGenerationService::class)->createRequest(
            'EDIT REQUEST - change the scene keeping the character.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $missing->id],
            null,
        );

        (new GenerateImageJob($reqMissing->id))->handle(app(ImageGenerationService::class));

        $this->assertSame(ImageGenerationRequestStatus::Failed, $reqMissing->fresh()->status);
        $this->assertSame('source_missing', $reqMissing->fresh()->error_code);

        $invalid = $this->scriptAsset($script);
        Storage::disk('public')->put($invalid->path, 'corrompido');
        $reqInvalid = app(ImageGenerationService::class)->createRequest(
            'EDIT REQUEST - change the scene keeping the character.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $invalid->id],
            null,
        );

        (new GenerateImageJob($reqInvalid->id))->handle(app(ImageGenerationService::class));

        $this->assertSame('source_invalid', $reqInvalid->fresh()->error_code);
        Http::assertNothingSent();
        $this->assertSame(0, MediaAsset::whereNotNull('parent_media_asset_id')->count());
    }

    // ---- primary / segurança / builder / gallery ----

    public function test_output_primary_demite_anterior(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $script = $this->context();
        $oldPrimary = $this->scriptAsset($script);
        $script->mediaAssets()->updateExistingPivot($oldPrimary->id, ['is_primary' => true]);
        $source = $this->scriptAsset($script);

        $request = app(ImageGenerationService::class)->createRequest(
            'EDIT REQUEST - change the scene keeping the character.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $source->id, 'is_primary' => true],
            null,
        );

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $output = MediaAsset::where('parent_media_asset_id', $source->id)->firstOrFail();
        $outputPivot = $script->fresh()->mediaAssets()->whereKey($output->id)->firstOrFail()->pivot;
        $oldPivot = $script->fresh()->mediaAssets()->whereKey($oldPrimary->id)->firstOrFail()->pivot;
        $this->assertTrue((bool) $outputPivot->is_primary);
        $this->assertFalse((bool) $oldPivot->is_primary);
        $this->assertDatabaseHas('media_assets', ['id' => $source->id]);
    }

    public function test_sem_base64_no_banco(): void
    {
        Storage::fake('public');
        $this->enableImage();
        $this->fakeImage();
        $script = $this->context();
        $source = $this->scriptAsset($script);
        $binary = $this->png600();
        Storage::disk('public')->put($source->path, $binary);

        $request = app(ImageGenerationService::class)->createRequest(
            'EDIT REQUEST - change the scene keeping the character.',
            ['content_script_id' => $script->id, 'source_media_asset_id' => $source->id],
            null,
        );

        (new GenerateImageJob($request->id))->handle(app(ImageGenerationService::class));

        $dump = json_encode([
            ImageGenerationRequest::firstOrFail()->toArray(),
            MediaAsset::where('parent_media_asset_id', $source->id)->firstOrFail()->toArray(),
            AiGeneration::firstOrFail()->toArray(),
        ]);
        $this->assertStringNotContainsString(base64_encode($binary), (string) $dump);
    }

    public function test_builder_estrutura(): void
    {
        $script = $this->context();
        $source = MediaAsset::factory()->create();

        $prompt = app(ImageEditPromptBuilder::class)->build(
            'Troque o ambiente para uma sala moderna.',
            $script, $script->product, $script->blueprint, $script->persona, $script->avatar, $source, 2,
        );

        $this->assertStringContainsString('EDIT REQUEST', $prompt);
        $this->assertStringContainsString('source composition to transform', $prompt);
        $this->assertStringContainsString('reference images only', $prompt);
        $this->assertStringContainsString('fictional AI character', $prompt);
        $this->assertStringContainsString('Do not render text unless explicitly requested', $prompt);
    }

    public function test_gallery_mostra_variacao(): void
    {
        $user = User::factory()->create();
        $script = $this->context();
        $source = MediaAsset::factory()->create();
        $script->mediaAssets()->attach($source->id, ['purpose' => 'scene', 'is_primary' => false]);
        $output = MediaAsset::factory()->create(['parent_media_asset_id' => $source->id]);
        $script->mediaAssets()->attach($output->id, ['purpose' => 'scene', 'is_primary' => false]);

        $this->withoutVite()->actingAs($user)->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('Criar variação', false)
            ->assertSee('Variação', false);
    }
}
