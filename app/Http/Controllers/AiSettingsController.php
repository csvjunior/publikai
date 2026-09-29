<?php

namespace App\Http\Controllers;

use App\AI\Exceptions\AiProviderException;
use App\Services\AiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('access-admin');

        $google = config('ai.google');

        return view('settings.ai', [
            'provider' => 'Google Gemini',
            'model' => (string) $google['model'],
            'configured' => (bool) $google['enabled'] && (string) $google['auth_key'] !== '',
        ]);
    }

    public function test(AiService $service): RedirectResponse
    {
        $this->authorize('access-admin');

        try {
            $result = $service->testConnection();
        } catch (AiProviderException $e) {
            return back()->with('ai_test', [
                'ok' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ]);
        }

        return back()->with('ai_test', [
            'ok' => true,
            'status' => $result->data['status'] ?? 'ok',
            'message' => $result->data['message'] ?? 'Conexão validada.',
            'duration_ms' => $result->durationMs,
        ]);
    }
}
