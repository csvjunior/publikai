<?php

namespace App\Services;

use App\Enums\ContentProductionStatus;
use App\Enums\ContentProductionStep;
use App\Enums\ContentScriptStatus;
use App\Jobs\GenerateAudioJob;
use App\Jobs\GenerateImageJob;
use App\Jobs\GenerateVideoJob;
use App\Jobs\MergeAudioVideoJob;
use App\Models\AudioGenerationRequest;
use App\Models\AudioVideoMergeRequest;
use App\Models\ContentProduction;
use App\Models\ContentScript;
use App\Models\ImageGenerationRequest;
use App\Models\MediaAsset;
use App\Models\VideoGenerationRequest;
use Illuminate\Support\Facades\DB;

/**
 * Orquestrador de produção de vídeo (Sprint 5.6.5).
 * UMA ação do usuário; etapas internas image → video → audio → merge,
 * com reuse-first determinístico e sem retry caro automático.
 * advance() executa só passos síncronos; ao precisar de provider/FFmpeg,
 * cria o request filho, despacha o Job existente e retorna (a continuação
 * volta via RelaysContentProduction). Sem polling, sem espera bloqueante.
 */
class ContentProductionService
{
    public function __construct(
        protected ProductionFlowService $flow,
        protected ImageGenerationService $images,
        protected VideoGenerationService $videos,
        protected AudioGenerationService $audios,
        protected AudioVideoMergeService $merges,
        protected VisualPromptBuilder $imagePrompts,
        protected VideoMotionPromptBuilder $motionPrompts,
        protected NarrationTextBuilder $narrations,
    ) {}

    /**
     * @return array{production: ContentProduction, created: bool, resumed: bool}
     */
    public function start(ContentScript $script, bool $forceNew, ?int $createdBy): array
    {
        abort_unless(
            in_array($script->status, [ContentScriptStatus::Ready, ContentScriptStatus::Approved], true),
            403,
            'Produção só para roteiros prontos ou aprovados.'
        );

        return DB::transaction(function () use ($script, $forceNew, $createdBy) {
            $active = ContentProduction::where('content_script_id', $script->id)
                ->whereIn('status', [ContentProductionStatus::Pending, ContentProductionStatus::Processing])
                ->lockForUpdate()
                ->first();

            if ($active) {
                return ['production' => $active, 'created' => false, 'resumed' => false];
            }

            // Retry: mesma produção falhada continua do ponto da falha,
            // reutilizando snapshots (sem regenerar etapas válidas).
            if (! $forceNew) {
                $failed = ContentProduction::where('content_script_id', $script->id)
                    ->where('status', ContentProductionStatus::Failed)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($failed) {
                    $failed->update([
                        'status' => ContentProductionStatus::Pending,
                        'current_step' => $this->resumeStep($failed->fresh()),
                        'error_code' => null,
                        'error_message' => null,
                        'completed_at' => null,
                    ]);

                    return ['production' => $failed->fresh(), 'created' => false, 'resumed' => true];
                }
            }

            $production = ContentProduction::create([
                'status' => ContentProductionStatus::Pending,
                'current_step' => ContentProductionStep::Preparing,
                'force_new' => $forceNew,
                'content_script_id' => $script->id,
                'created_by' => $createdBy,
            ]);

            return ['production' => $production, 'created' => true, 'resumed' => false];
        });
    }

    protected function resumeStep(ContentProduction $production): ContentProductionStep
    {
        if (! $production->image_media_asset_id) {
            return ContentProductionStep::Image;
        }

        if (! $production->video_media_asset_id) {
            return ContentProductionStep::Video;
        }

        if (! $production->audio_media_asset_id) {
            return ContentProductionStep::Audio;
        }

        return ContentProductionStep::Finalizing;
    }

    /**
     * Avança o máximo possível sem bloquear. Reutiliza assets/etapas;
     * ao precisar de execução assíncrona, despacha e retorna.
     */
    public function advance(int $productionId): void
    {
        $production = ContentProduction::find($productionId);

        if (! $production || $production->isTerminal()) {
            return;
        }

        $script = $production->script;
        $script->load(['product', 'blueprint', 'persona', 'avatar']);

        // Output do próprio merge: conclui.
        $ownFinal = $this->ownMergeOutput($production);

        if ($ownFinal) {
            $this->succeed($production, $ownFinal);

            return;
        }

        // Final válido do script (sem force): conclui sem gerar nada.
        if (! $production->force_new && ($final = $this->reusableFinal($script))) {
            $this->succeed($production, $final->id);

            return;
        }

        if (! $production->image_media_asset_id
            && ! $this->adoptChildSuccess($production, ImageGenerationRequest::class, 'image_media_asset_id')
        ) {
            $this->advanceImage($production, $script);

            return;
        }

        if (! $production->video_media_asset_id
            && ! $this->adoptChildSuccess($production, VideoGenerationRequest::class, 'video_media_asset_id')
        ) {
            $this->advanceVideo($production, $script);

            return;
        }

        if (! $production->audio_media_asset_id
            && ! $this->adoptChildSuccess($production, AudioGenerationRequest::class, 'audio_media_asset_id')
        ) {
            $this->advanceAudio($production, $script);

            return;
        }

        $this->advanceFinal($production, $script);
    }

    /**
     * Continuação chamada pelos Jobs filhos (via RelaysContentProduction).
     */
    public function childFinished(int $productionId): void
    {
        $production = ContentProduction::find($productionId);

        if (! $production || $production->isTerminal()) {
            return;
        }

        $failed = $this->latestFailedChild($production);

        if ($failed) {
            $this->fail($production, $failed['code'], $failed['message']);

            return;
        }

        $this->advance($productionId);
    }

    protected function ownMergeOutput(ContentProduction $production): ?int
    {
        $merge = AudioVideoMergeRequest::where('content_production_id', $production->id)
            ->where('status', 'success')
            ->latest('id')
            ->first();

        return $merge?->output_media_asset_id;
    }

    /**
     * @param  class-string  $requestClass
     */
    protected function adoptChildSuccess(ContentProduction $production, string $requestClass, string $column): bool
    {
        $child = $requestClass::where('content_production_id', $production->id)
            ->where('status', 'success')
            ->latest('id')
            ->first();

        if (! $child || ! $child->media_asset_id) {
            return false;
        }

        $production->update([$column => $child->media_asset_id]);

        return true;
    }

    /**
     * @return array{code: string, message: string}|null
     */
    protected function latestFailedChild(ContentProduction $production): ?array
    {
        $map = [
            ImageGenerationRequest::class => 'a imagem',
            VideoGenerationRequest::class => 'o vídeo',
            AudioGenerationRequest::class => 'a narração',
            AudioVideoMergeRequest::class => 'o vídeo final',
        ];

        foreach ($map as $class => $label) {
            $child = $class::where('content_production_id', $production->id)
                ->latest('id')
                ->first();

            if ($child && $child->status->value === 'failed') {
                return [
                    'code' => $child->error_code ?? 'provider_failed',
                    'message' => 'Não foi possível concluir '.$label.'.',
                ];
            }
        }

        return null;
    }

    /**
     * Falha segura do orchestrator (auditoria async): exceção inesperada
     * nunca deixa production presa em pending/processing. Idempotente.
     */
    public function failUnexpected(int $productionId): void
    {
        $production = ContentProduction::find($productionId);

        if (! $production || $production->isTerminal()) {
            return;
        }

        $production->update([
            'status' => ContentProductionStatus::Failed,
            'error_code' => 'internal_error',
            'error_message' => 'Não foi possível concluir o vídeo.',
            'completed_at' => now(),
        ]);
    }

    protected function waitingChild(ContentProduction $production, string $requestClass): bool
    {
        $statuses = $requestClass === VideoGenerationRequest::class
            ? ['pending', 'processing', 'starting']
            : ['pending', 'processing'];

        return $requestClass::where('content_production_id', $production->id)
            ->whereIn('status', $statuses)
            ->exists();
    }

    /**
     * Recupera child pending (criada mas nunca despachada/executada):
     * re-despacha o Job com segurança. Processing (worker ativo) apenas
     * aguarda — nunca duplica chamada paga.
     *
     * @param  class-string  $requestClass
     * @param  class-string  $jobClass
     */
    protected function recoverPendingChild(ContentProduction $production, string $requestClass, string $jobClass): bool
    {
        $pending = $requestClass::where('content_production_id', $production->id)
            ->where('status', 'pending')
            ->oldest('id')
            ->first();

        if (! $pending) {
            return false;
        }

        $jobClass::dispatch($pending->id);

        return true;
    }

    protected function advanceImage(ContentProduction $production, ContentScript $script): void
    {
        if ($this->waitingChild($production, ImageGenerationRequest::class)) {
            $this->recoverPendingChild($production, ImageGenerationRequest::class, GenerateImageJob::class);
            $this->markStep($production, ContentProductionStep::Image);

            return;
        }

        $flow = $this->flow->for($script);

        if ($flow['recommended_image_id'] && ($asset = MediaAsset::find($flow['recommended_image_id']))) {
            $production->update([
                'status' => ContentProductionStatus::Processing,
                'current_step' => ContentProductionStep::Video,
                'image_media_asset_id' => $asset->id,
                'started_at' => $production->started_at ?? now(),
            ]);
            $this->advance($production->id);

            return;
        }

        $avatar = $script->avatar;

        $this->markStep($production, ContentProductionStep::Image);

        $request = $this->images->createRequest(
            $this->imagePrompts->build(
                $script,
                $script->product,
                $script->blueprint,
                $script->persona,
                $avatar,
                $avatar->referenceImages()->count(),
            ),
            [
                'aspect_ratio' => '9:16',
                'image_size' => '1K',
                'mime_type' => 'image/jpeg',
                'content_script_id' => $script->id,
                'content_production_id' => $production->id,
                'purpose' => 'scene',
                'is_primary' => false,
            ],
            $production->created_by,
        );

        GenerateImageJob::dispatch($request->id);
    }

    protected function advanceVideo(ContentProduction $production, ContentScript $script): void
    {
        if ($this->waitingChild($production, VideoGenerationRequest::class)) {
            $this->recoverPendingChild($production, VideoGenerationRequest::class, GenerateVideoJob::class);
            $this->markStep($production, ContentProductionStep::Video);

            return;
        }

        if ($production->force_new) {
            $reuseId = null;
        } else {
            $reuseId = $this->flow->for($script)['recommended_video_id'];
        }

        if ($reuseId && ($asset = MediaAsset::find($reuseId))) {
            $production->update([
                'status' => ContentProductionStatus::Processing,
                'current_step' => ContentProductionStep::Audio,
                'video_media_asset_id' => $asset->id,
                'started_at' => $production->started_at ?? now(),
            ]);
            $this->advance($production->id);

            return;
        }

        $image = MediaAsset::findOrFail((int) $production->image_media_asset_id);

        $this->markStep($production, ContentProductionStep::Video);

        $request = $this->videos->createRequest(
            $this->motionPrompts->build(
                $script,
                $script->product,
                $script->blueprint,
                $script->persona,
                $script->avatar,
            ),
            [
                'aspect_ratio' => '9:16',
                'duration_seconds' => 8,
                'content_script_id' => $script->id,
                'content_production_id' => $production->id,
                'source_media_asset_id' => $image->id,
            ],
            $production->created_by,
        );

        GenerateVideoJob::dispatch($request->id);
    }

    protected function advanceAudio(ContentProduction $production, ContentScript $script): void
    {
        if ($this->waitingChild($production, AudioGenerationRequest::class)) {
            $this->recoverPendingChild($production, AudioGenerationRequest::class, GenerateAudioJob::class);
            $this->markStep($production, ContentProductionStep::Audio);

            return;
        }

        $reuseId = $this->flow->for($script)['recommended_audio_id'];

        if ($reuseId && ($asset = MediaAsset::find($reuseId))) {
            $production->update([
                'status' => ContentProductionStatus::Processing,
                'current_step' => ContentProductionStep::Finalizing,
                'audio_media_asset_id' => $asset->id,
                'started_at' => $production->started_at ?? now(),
            ]);
            $this->advance($production->id);

            return;
        }

        $script->loadMissing(['persona']);

        $this->markStep($production, ContentProductionStep::Audio);

        $request = $this->audios->createRequest(
            $this->narrations->build($script),
            [
                'voice' => $this->audios->defaultVoice(),
                'language' => $script->language,
                'style' => $script->persona?->tone,
                'content_script_id' => $script->id,
                'content_production_id' => $production->id,
            ],
            $production->created_by,
        );

        GenerateAudioJob::dispatch($request->id);
    }

    protected function advanceFinal(ContentProduction $production, ContentScript $script): void
    {
        if ($this->waitingChild($production, AudioVideoMergeRequest::class)) {
            $this->recoverPendingChild($production, AudioVideoMergeRequest::class, MergeAudioVideoJob::class);
            $this->markFinalizing($production);

            return;
        }

        $this->markFinalizing($production);

        $request = $this->merges->createRequest(
            $script->id,
            (int) $production->video_media_asset_id,
            (int) $production->audio_media_asset_id,
            $production->created_by,
            $production->id,
        );

        MergeAudioVideoJob::dispatch($request->id);
    }

    protected function markStep(ContentProduction $production, ContentProductionStep $step): void
    {
        $data = [
            'status' => ContentProductionStatus::Processing,
            'current_step' => $step,
        ];

        if (! $production->started_at) {
            $data['started_at'] = now();
        }

        $production->update($data);
    }

    protected function markFinalizing(ContentProduction $production): void
    {
        $this->markStep($production, ContentProductionStep::Finalizing);
    }

    protected function succeed(ContentProduction $production, int $finalAssetId): void
    {
        $production->update([
            'status' => ContentProductionStatus::Success,
            'current_step' => ContentProductionStep::Completed,
            'final_media_asset_id' => $finalAssetId,
            'error_code' => null,
            'error_message' => null,
            'completed_at' => now(),
        ]);
    }

    protected function fail(ContentProduction $production, string $code, string $message): void
    {
        $production->update([
            'status' => ContentProductionStatus::Failed,
            'error_code' => $code,
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }

    protected function reusableFinal(ContentScript $script): ?MediaAsset
    {
        $flow = $this->flow->for($script);
        $id = $flow['final']['asset']?->id;

        return $id ? MediaAsset::find($id) : null;
    }
}
