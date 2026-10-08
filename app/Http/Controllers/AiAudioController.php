<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestAudioGenerationRequest;
use App\Jobs\GenerateAudioJob;
use App\Models\AudioGenerationRequest;
use App\Services\AudioGenerationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiAudioController extends Controller
{
    use AuthorizesRequests;

    public function index(AudioGenerationService $service): View
    {
        $this->authorize('access-admin');

        $audio = config('ai.google.audio');

        return view('settings.ai.audio', [
            'provider' => 'Google Gemini TTS',
            'model' => (string) $audio['model'],
            'configured' => (bool) $audio['enabled'] && (string) config('ai.google.auth_key') !== '',
            'voices' => $service->voices(),
            'defaultVoice' => $service->defaultVoice(),
            'recent' => AudioGenerationRequest::latest()->limit(10)->get(),
        ]);
    }

    public function store(TestAudioGenerationRequest $request, AudioGenerationService $service): RedirectResponse
    {
        $this->authorize('access-admin');

        $audio = config('ai.google.audio');
        $configured = (bool) $audio['enabled'] && (string) config('ai.google.auth_key') !== '';

        if (! $configured) {
            return back()->withInput()->with('audio_test', [
                'ok' => false,
                'message' => 'IA de narração não configurada. Defina GOOGLE_AI_AUDIO_ENABLED e GOOGLE_AI_AUTH_KEY no .env.',
                'error_code' => 'not_configured',
            ]);
        }

        $generation = $service->createRequest(
            (string) $request->string('text'),
            $request->only(['voice']),
            auth()->id(),
        );

        GenerateAudioJob::dispatch($generation->id);

        return redirect()->route('settings.ai.audio')->with('audio_test', [
            'ok' => true,
            'started' => true,
            'message' => 'Geração de narração iniciada. Atualize a página para acompanhar.',
        ]);
    }
}
