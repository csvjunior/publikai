<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestVideoGenerationRequest;
use App\Jobs\GenerateVideoJob;
use App\Models\MediaAsset;
use App\Models\VideoGenerationRequest;
use App\Services\VideoGenerationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiVideoController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('access-admin');

        $video = config('ai.google.video');

        return view('settings.ai.videos', [
            'provider' => 'Google Omni',
            'model' => (string) $video['model'],
            'configured' => (bool) $video['enabled'] && (string) config('ai.google.auth_key') !== '',
            'sources' => MediaAsset::where('type', 'image')->latest()->limit(50)->get(),
            'recent' => VideoGenerationRequest::latest()->limit(10)->get(),
        ]);
    }

    public function store(TestVideoGenerationRequest $request, VideoGenerationService $service): RedirectResponse
    {
        $this->authorize('access-admin');

        $video = config('ai.google.video');
        $configured = (bool) $video['enabled'] && (string) config('ai.google.auth_key') !== '';

        if (! $configured) {
            return back()->withInput()->with('video_test', [
                'ok' => false,
                'message' => 'IA de vídeo não configurada. Defina GOOGLE_AI_VIDEO_ENABLED e GOOGLE_AI_AUTH_KEY no .env.',
                'error_code' => 'not_configured',
            ]);
        }

        $generation = $service->createRequest(
            (string) $request->string('prompt'),
            $request->only(['aspect_ratio', 'duration_seconds', 'source_media_asset_id']),
            auth()->id(),
        );

        GenerateVideoJob::dispatch($generation->id);

        return redirect()->route('settings.ai.videos')->with('video_test', [
            'ok' => true,
            'started' => true,
            'message' => 'Geração de vídeo iniciada. Atualize a página para acompanhar.',
        ]);
    }
}
