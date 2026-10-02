<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageGenerationRequest;
use App\Jobs\GenerateImageJob;
use App\Models\MediaAsset;
use App\Services\ImageGenerationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiImageController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('access-admin');

        $image = config('ai.google.image');

        $preview = request()->query('preview')
            ? MediaAsset::find(request()->query('preview'))
            : null;

        $recent = MediaAsset::where('type', 'image')->latest()->limit(10)->get();

        $generations = \App\Models\ImageGenerationRequest::latest()->limit(10)->get();

        return view('settings.ai.images', [
            'provider' => 'Google Gemini',
            'model' => (string) $image['model'],
            'configured' => (bool) $image['enabled'] && (string) config('ai.google.auth_key') !== '',
            'preview' => $preview,
            'recent' => $recent,
            'generations' => $generations,
        ]);
    }

    public function store(ImageGenerationRequest $request, ImageGenerationService $service): RedirectResponse
    {
        $this->authorize('access-admin');

        $image = config('ai.google.image');
        $configured = (bool) $image['enabled'] && (string) config('ai.google.auth_key') !== '';

        if (! $configured) {
            return back()->withInput()->with('image_test', [
                'ok' => false,
                'message' => 'IA de imagem não configurada. Defina GOOGLE_AI_IMAGE_ENABLED e GOOGLE_AI_AUTH_KEY no .env.',
                'error_code' => 'not_configured',
            ]);
        }

        $generation = $service->createRequest(
            (string) $request->string('prompt'),
            $request->only(['aspect_ratio', 'image_size', 'mime_type']),
            auth()->id(),
        );

        GenerateImageJob::dispatch($generation->id);

        return redirect()->route('settings.ai.images')->with('image_test', [
            'ok' => true,
            'started' => true,
            'message' => 'Geração iniciada. Atualize a página para acompanhar.',
        ]);
    }
}
