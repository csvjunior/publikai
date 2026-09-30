<?php

namespace App\Http\Controllers;

use App\Exceptions\AnalysisInProgressException;
use App\Exceptions\InsufficientAnalysisContextException;
use App\Models\ReferenceProfile;
use App\Services\ReferenceAnalysisService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ReferenceAnalysisController extends Controller
{
    use AuthorizesRequests;

    public function store(ReferenceProfile $referenceProfile, ReferenceAnalysisService $service): RedirectResponse
    {
        $this->authorize('view', $referenceProfile);

        try {
            $analysis = $service->analyze($referenceProfile);
        } catch (AnalysisInProgressException|InsufficientAnalysisContextException $e) {
            return back()->with('analysis_notice', $e->getMessage());
        }

        if ($analysis->isSuccess()) {
            return back()->with('status', 'Análise concluída. Veja o resultado abaixo.');
        }

        return back()->with('analysis_error', $this->friendlyMessage($analysis->error_code));
    }

    protected function friendlyMessage(?string $errorCode): string
    {
        return match ($errorCode) {
            'service_unavailable' => 'O serviço de IA está temporariamente indisponível. Tente novamente mais tarde.',
            'rate_limited' => 'O limite temporário do serviço de IA foi atingido. Tente novamente mais tarde.',
            'timeout' => 'O serviço de IA demorou mais que o esperado para responder. Tente novamente.',
            default => 'Não foi possível concluir a análise agora. Tente novamente mais tarde.',
        };
    }
}
