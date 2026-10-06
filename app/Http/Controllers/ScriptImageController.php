<?php

namespace App\Http\Controllers;

use App\Enums\ContentScriptStatus;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Http\Requests\ScriptImageGenerationRequest;
use App\Http\Requests\ScriptImageVariationRequest;
use App\Jobs\GenerateImageJob;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Services\AvatarReferenceService;
use App\Services\ImageEditPromptBuilder;
use App\Services\ImageGenerationService;
use App\Services\VisualPromptBuilder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScriptImageController extends Controller
{
    use AuthorizesRequests;

    public function create(
        ContentScript $contentScript,
        VisualPromptBuilder $builder,
        AvatarReferenceService $referenceService,
    ): View {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        $contentScript->load(['product', 'blueprint', 'persona', 'avatar.referenceImages']);

        $references = $contentScript->avatar?->referenceImages ?? collect();

        return view('scripts.images.create', [
            'script' => $contentScript,
            'prompt' => old('prompt', $builder->build(
                $contentScript,
                $contentScript->product,
                $contentScript->blueprint,
                $contentScript->persona,
                $contentScript->avatar,
                $references->count(),
            )),
            'aiConfigured' => $this->aiConfigured(),
            'references' => $references,
            'maxReferences' => $referenceService->maxReferences(),
        ]);
    }

    public function store(
        ScriptImageGenerationRequest $request,
        ContentScript $contentScript,
        ImageGenerationService $service,
    ): RedirectResponse {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        if (! $this->aiConfigured()) {
            return back()->withInput()->with('image_notice', 'Configure a geração de imagens em Sistema → IA.');
        }

        $generation = $service->createRequest(
            (string) $request->string('prompt'),
            [
                'aspect_ratio' => $request->input('aspect_ratio'),
                'image_size' => $request->input('image_size'),
                'mime_type' => $request->input('mime_type'),
                'content_script_id' => $contentScript->id,
                'reference_media_asset_ids' => $this->resolveReferenceIds($request, $contentScript),
                'purpose' => $request->input('purpose', 'scene'),
                'is_primary' => $request->boolean('is_primary'),
            ],
            auth()->id(),
        );

        GenerateImageJob::dispatch($generation->id);

        return redirect()->route('scripts.show', $contentScript)->with(
            'status',
            'Geração de imagem iniciada. Atualize a página para acompanhar.'
        );
    }

    /**
     * Snapshot das referências (Sprint 5.5.3): default = todas do Avatar
     * (primary primeiro); seleção humana explícita; Visual DNA only = [].
     * IDs fora do Avatar são rejeitados (422).
     *
     * @return int[]
     */
    protected function resolveReferenceIds(FormRequest $request, ContentScript $contentScript): array
    {
        $available = $contentScript->avatar
            ? $contentScript->avatar->referenceImages()->pluck('media_assets.id')->all()
            : [];

        if ($request->boolean('visual_dna_only')) {
            return [];
        }

        $selected = $request->input('reference_ids');

        if ($selected === null) {
            return $available;
        }

        $selected = array_values(array_unique(array_map('intval', (array) $selected)));

        abort_if(
            array_diff($selected, array_map('intval', $available)) !== []
                || ($selected !== [] && $available === []),
            422,
            'Referência visual inválida para este Avatar.'
        );

        return $selected;
    }

    public function createVariation(
        ContentScript $contentScript,
        MediaAsset $mediaAsset,
        AvatarReferenceService $referenceService,
    ): View {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);
        $source = $this->resolveSource($contentScript, $mediaAsset);

        $contentScript->load(['product', 'blueprint', 'persona', 'avatar.referenceImages']);

        $references = $contentScript->avatar?->referenceImages ?? collect();

        return view('scripts.images.edit', [
            'script' => $contentScript,
            'source' => $source,
            'sourcePurpose' => $source->pivot->purpose ?? 'scene',
            'defaultAspectRatio' => $this->aspectRatioFor($source),
            'aiConfigured' => $this->aiConfigured(),
            'references' => $references,
            'maxReferences' => $referenceService->maxReferences(),
        ]);
    }

    public function storeVariation(
        ScriptImageVariationRequest $request,
        ContentScript $contentScript,
        MediaAsset $mediaAsset,
        ImageEditPromptBuilder $builder,
        ImageGenerationService $service,
    ): RedirectResponse {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);
        $source = $this->resolveSource($contentScript, $mediaAsset);

        if (! $this->aiConfigured()) {
            return back()->withInput()->with('image_notice', 'Configure a geração de imagens em Sistema → IA.');
        }

        $contentScript->load(['product', 'blueprint', 'persona', 'avatar.referenceImages']);

        $references = $contentScript->avatar?->referenceImages ?? collect();

        $generation = $service->createRequest(
            $builder->build(
                (string) $request->string('change'),
                $contentScript,
                $contentScript->product,
                $contentScript->blueprint,
                $contentScript->persona,
                $contentScript->avatar,
                $source,
                $references->count(),
            ),
            [
                'aspect_ratio' => $request->input('aspect_ratio'),
                'image_size' => $request->input('image_size'),
                'mime_type' => $request->input('mime_type'),
                'content_script_id' => $contentScript->id,
                'source_media_asset_id' => $source->id,
                'reference_media_asset_ids' => $this->resolveReferenceIds($request, $contentScript),
                'purpose' => $request->input('purpose', $source->pivot->purpose ?? 'scene'),
                'is_primary' => $request->boolean('is_primary'),
            ],
            auth()->id(),
        );

        GenerateImageJob::dispatch($generation->id);

        return redirect()->route('scripts.show', $contentScript)->with(
            'status',
            'Variação de imagem iniciada. Atualize a página para acompanhar.'
        );
    }

    /**
     * Source válida da variação (Sprint 5.5.4): pertence ao Script, é
     * imagem pronta com arquivo válido. Original nunca é alterado.
     */
    protected function resolveSource(ContentScript $contentScript, MediaAsset $mediaAsset): MediaAsset
    {
        $source = $contentScript->mediaAssets()->whereKey($mediaAsset->id)->first();

        abort_unless($source, 404);
        abort_unless(
            $source->type === MediaAssetType::Image
                && $source->status === MediaAssetStatus::Ready
                && $source->url() !== null,
            422,
            'A imagem base não está disponível para variação.'
        );

        return $source;
    }

    /**
     * Proporção suportada mais próxima da source (Sprint 5.5.4).
     */
    protected function aspectRatioFor(MediaAsset $source): string
    {
        if (! $source->width || ! $source->height) {
            return '9:16';
        }

        $ratio = $source->width / $source->height;
        $best = '9:16';
        $bestDiff = PHP_FLOAT_MAX;

        foreach (['1:1' => 1.0, '4:5' => 0.8, '9:16' => 9 / 16, '16:9' => 16 / 9] as $name => $value) {
            $diff = abs($ratio - $value);

            if ($diff < $bestDiff) {
                $best = $name;
                $bestDiff = $diff;
            }
        }

        return $best;
    }

    public function markPrimary(
        ContentScript $contentScript,
        MediaAsset $mediaAsset,
        ImageGenerationService $service,
    ): RedirectResponse {
        $this->authorize('update', $contentScript);

        abort_unless(
            $contentScript->mediaAssets()->whereKey($mediaAsset->id)->exists(),
            404
        );

        $service->markPrimary($contentScript, $mediaAsset);

        return back()->with('status', 'Imagem definida como principal.');
    }

    protected function ensureEligible(ContentScript $contentScript): void
    {
        abort_unless(
            in_array($contentScript->status, [ContentScriptStatus::Ready, ContentScriptStatus::Approved], true),
            403,
            'Imagens só podem ser geradas para roteiros prontos ou aprovados.'
        );
    }

    protected function aiConfigured(): bool
    {
        $image = config('ai.google.image');

        return (bool) $image['enabled'] && (string) config('ai.google.auth_key') !== '';
    }
}
