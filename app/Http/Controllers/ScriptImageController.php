<?php

namespace App\Http\Controllers;

use App\Enums\ContentScriptStatus;
use App\Http\Requests\ScriptImageGenerationRequest;
use App\Jobs\GenerateImageJob;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Services\ImageGenerationService;
use App\Services\VisualPromptBuilder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScriptImageController extends Controller
{
    use AuthorizesRequests;

    public function create(
        ContentScript $contentScript,
        VisualPromptBuilder $builder,
    ): View {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        $contentScript->load(['product', 'blueprint', 'persona', 'avatar']);

        return view('scripts.images.create', [
            'script' => $contentScript,
            'prompt' => old('prompt', $builder->build(
                $contentScript,
                $contentScript->product,
                $contentScript->blueprint,
                $contentScript->persona,
                $contentScript->avatar,
            )),
            'aiConfigured' => $this->aiConfigured(),
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
