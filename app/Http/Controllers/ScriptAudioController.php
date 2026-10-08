<?php

namespace App\Http\Controllers;

use App\Enums\ContentScriptStatus;
use App\Http\Requests\ScriptAudioGenerationRequest;
use App\Jobs\GenerateAudioJob;
use App\Models\ContentScript;
use App\Services\AudioGenerationService;
use App\Services\NarrationTextBuilder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScriptAudioController extends Controller
{
    use AuthorizesRequests;

    public function create(
        ContentScript $contentScript,
        NarrationTextBuilder $builder,
        AudioGenerationService $service,
    ): View {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        $contentScript->load(['persona']);

        return view('scripts.audio.create', [
            'script' => $contentScript,
            'text' => old('text', $builder->build($contentScript)),
            'voices' => $service->voices(),
            'defaultVoice' => $service->defaultVoice(),
            'aiConfigured' => $this->aiConfigured(),
        ]);
    }

    public function store(
        ScriptAudioGenerationRequest $request,
        ContentScript $contentScript,
        AudioGenerationService $service,
    ): RedirectResponse {
        $this->authorize('update', $contentScript);
        $this->ensureEligible($contentScript);

        if (! $this->aiConfigured()) {
            return back()->withInput()->with('audio_notice', 'Configure a geração de narrações em Sistema → IA.');
        }

        $contentScript->load(['persona']);

        $generation = $service->createRequest(
            (string) $request->string('text'),
            [
                'voice' => $request->input('voice'),
                'language' => $contentScript->language,
                'style' => $contentScript->persona?->tone,
                'content_script_id' => $contentScript->id,
            ],
            auth()->id(),
        );

        GenerateAudioJob::dispatch($generation->id);

        return redirect()->route('scripts.show', $contentScript)->with(
            'status',
            'Geração de narração iniciada. Atualize a página para acompanhar.'
        );
    }

    protected function ensureEligible(ContentScript $contentScript): void
    {
        abort_unless(
            in_array($contentScript->status, [ContentScriptStatus::Ready, ContentScriptStatus::Approved], true),
            403,
            'Narrações só podem ser geradas para roteiros prontos ou aprovados.'
        );
    }

    protected function aiConfigured(): bool
    {
        $audio = config('ai.google.audio');

        return (bool) $audio['enabled'] && (string) config('ai.google.auth_key') !== '';
    }
}
