<?php

namespace App\Http\Controllers;

use App\Enums\ContentScriptStatus;
use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Http\Requests\ScriptAudioVideoMergeRequest;
use App\Jobs\MergeAudioVideoJob;
use App\Models\ContentScript;
use App\Models\MediaAsset;
use App\Services\AudioVideoMergeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ScriptAudioVideoController extends Controller
{
    use AuthorizesRequests;

    public function create(ContentScript $contentScript): View
    {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        return view('scripts.merges.create', [
            'script' => $contentScript,
            'videos' => $this->eligibleVideos($contentScript),
            'audios' => $this->eligibleAudios($contentScript),
        ]);
    }

    public function store(
        ScriptAudioVideoMergeRequest $request,
        ContentScript $contentScript,
        AudioVideoMergeService $service,
    ): RedirectResponse {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        $merge = $service->createRequest(
            $contentScript->id,
            (int) $request->input('video_media_asset_id'),
            (int) $request->input('audio_media_asset_id'),
            auth()->id(),
        );

        MergeAudioVideoJob::dispatch($merge->id);

        return redirect()->route('scripts.show', $contentScript)->with(
            'status',
            'Merge de vídeo com narração iniciado. Atualize a página para acompanhar.'
        );
    }

    protected function ensureEligible(ContentScript $contentScript): void
    {
        abort_unless(
            in_array($contentScript->status, [ContentScriptStatus::Ready, ContentScriptStatus::Approved], true),
            403,
            'Merges só podem ser criados para roteiros prontos ou aprovados.'
        );
    }

    /**
     * @return Collection<int, MediaAsset>
     */
    protected function eligibleVideos(ContentScript $contentScript)
    {
        $ids = $contentScript->videoRequests()->whereNotNull('media_asset_id')->pluck('media_asset_id')
            ->merge($contentScript->compositions()->whereNotNull('output_media_asset_id')->pluck('output_media_asset_id'))
            ->merge($contentScript->merges()->whereNotNull('output_media_asset_id')->pluck('output_media_asset_id'))
            ->merge($contentScript->mediaAssets()->where('media_assets.type', 'video')->pluck('media_assets.id'))
            ->unique()->values();

        return MediaAsset::whereIn('id', $ids)
            ->where('type', MediaAssetType::Video)
            ->where('status', MediaAssetStatus::Ready)
            ->where('source', '!=', MediaAssetSource::Merged)
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, MediaAsset>
     */
    protected function eligibleAudios(ContentScript $contentScript)
    {
        $ids = $contentScript->audioRequests()->whereNotNull('media_asset_id')->pluck('media_asset_id')
            ->merge($contentScript->mediaAssets()->where('media_assets.type', 'audio')->pluck('media_assets.id'))
            ->unique()->values();

        return MediaAsset::whereIn('id', $ids)
            ->where('type', MediaAssetType::Audio)
            ->where('status', MediaAssetStatus::Ready)
            ->latest()
            ->get();
    }
}
