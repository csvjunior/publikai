<?php

namespace App\Http\Controllers;

use App\Enums\ContentScriptStatus;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Http\Requests\ScriptVideoGenerationRequest;
use App\Jobs\GenerateVideoJob;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Services\VideoGenerationService;
use App\Services\VideoPromptBuilder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScriptVideoController extends Controller
{
    use AuthorizesRequests;

    public function create(ContentScript $contentScript): View
    {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        $contentScript->load(['product', 'blueprint', 'persona', 'avatar']);

        $source = $this->resolveSource(
            $contentScript,
            (int) request()->query('source', 0)
        );

        return view('scripts.videos.create', [
            'script' => $contentScript,
            'source' => $source,
            'aiConfigured' => $this->aiConfigured(),
        ]);
    }

    public function store(
        ScriptVideoGenerationRequest $request,
        ContentScript $contentScript,
        VideoPromptBuilder $builder,
        VideoGenerationService $service,
    ): RedirectResponse {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        if (! $this->aiConfigured()) {
            return back()->withInput()->with('video_notice', 'Configure a geração de vídeos em Sistema → IA.');
        }

        $source = $this->resolveSource($contentScript, (int) $request->input('source_media_asset_id'));

        $contentScript->load(['product', 'blueprint', 'persona', 'avatar']);

        $generation = $service->createRequest(
            $builder->build(
                (string) $request->string('motion'),
                $contentScript,
                $contentScript->product,
                $contentScript->blueprint,
                $contentScript->persona,
                $contentScript->avatar,
                $source,
            ),
            [
                // Fundação 5.6.0: únicos valores suportados (browser não decide).
                'aspect_ratio' => '9:16',
                'duration_seconds' => 8,
                'content_script_id' => $contentScript->id,
                'source_media_asset_id' => $source->id,
            ],
            auth()->id(),
        );

        GenerateVideoJob::dispatch($generation->id);

        return redirect()->route('scripts.show', $contentScript)->with(
            'status',
            'Geração de vídeo iniciada. Atualize a página para acompanhar.'
        );
    }

    /**
     * Source válida (Sprint 5.6.0): pertence ao Script, image pronta.
     */
    protected function resolveSource(ContentScript $contentScript, int $mediaAssetId): MediaAsset
    {
        $source = $contentScript->mediaAssets()->whereKey($mediaAssetId)->first();

        abort_unless($source, 404);
        abort_unless(
            $source->type === MediaAssetType::Image && $source->status === MediaAssetStatus::Ready,
            422,
            'A imagem base não está disponível para vídeo.'
        );

        return $source;
    }

    protected function ensureEligible(ContentScript $contentScript): void
    {
        abort_unless(
            in_array($contentScript->status, [ContentScriptStatus::Ready, ContentScriptStatus::Approved], true),
            403,
            'Vídeos só podem ser gerados para roteiros prontos ou aprovados.'
        );
    }

    protected function aiConfigured(): bool
    {
        $video = config('ai.google.video');

        return (bool) $video['enabled'] && (string) config('ai.google.auth_key') !== '';
    }
}
