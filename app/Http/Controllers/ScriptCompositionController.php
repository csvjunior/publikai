<?php

namespace App\Http\Controllers;

use App\Enums\ContentScriptStatus;
use App\Http\Requests\ScriptVideoCompositionRequest;
use App\Jobs\ComposeVideoJob;
use App\Models\ContentScript;
use App\Services\VideoCompositionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScriptCompositionController extends Controller
{
    use AuthorizesRequests;

    public function create(ContentScript $contentScript): View
    {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        $contentScript->load(['mediaAssets']);

        $eligible = $contentScript->mediaAssets
            ->filter(fn ($asset) => in_array($asset->type->value, ['image', 'video'], true)
                && $asset->status->value === 'ready'
                && $asset->url() !== null)
            ->values();

        return view('scripts.compositions.create', [
            'script' => $contentScript,
            'eligible' => $eligible,
            'maxInputs' => (int) config('video-composition.max_inputs'),
        ]);
    }

    public function store(
        ScriptVideoCompositionRequest $request,
        ContentScript $contentScript,
        VideoCompositionService $service,
    ): RedirectResponse {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        $items = collect($request->input('items', []))
            ->filter(fn ($item) => ! empty($item['media_asset_id']))
            ->map(fn ($item) => [
                'media_asset_id' => (int) $item['media_asset_id'],
                'position' => (int) $item['position'],
                'trim_start_s' => $item['trim_start_s'] ?? null,
                'trim_end_s' => $item['trim_end_s'] ?? null,
                'image_duration_s' => $item['image_duration_s'] ?? null,
            ])
            ->all();

        $composition = $service->createRequest($contentScript->id, $items, auth()->id());

        ComposeVideoJob::dispatch($composition->id);

        return redirect()->route('scripts.show', $contentScript)->with(
            'status',
            'Composição iniciada. Atualize a página para acompanhar.'
        );
    }

    protected function ensureEligible(ContentScript $contentScript): void
    {
        abort_unless(
            in_array($contentScript->status, [ContentScriptStatus::Ready, ContentScriptStatus::Approved], true),
            403,
            'Composições só podem ser criadas para roteiros prontos ou aprovados.'
        );
    }
}
