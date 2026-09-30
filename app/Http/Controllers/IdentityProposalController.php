<?php

namespace App\Http\Controllers;

use App\Exceptions\AnalysisInProgressException;
use App\Exceptions\InsufficientAnalysisContextException;
use App\Http\Requests\UpdateIdentityProposalRequest;
use App\Models\IdentityProposal;
use App\Models\ReferenceProfile;
use App\Services\IdentityProposalService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class IdentityProposalController extends Controller
{
    use AuthorizesRequests;

    public function store(ReferenceProfile $referenceProfile, IdentityProposalService $service): RedirectResponse
    {
        $this->authorize('view', $referenceProfile);

        try {
            $service->generate($referenceProfile, auth()->id());
        } catch (AnalysisInProgressException|InsufficientAnalysisContextException $e) {
            return back()->with('proposal_notice', $e->getMessage());
        }

        return back()->with('status', 'Proposta gerada. Revise abaixo antes de aplicar.');
    }

    public function update(UpdateIdentityProposalRequest $request, ReferenceProfile $referenceProfile, IdentityProposal $identityProposal, IdentityProposalService $service): RedirectResponse
    {
        $this->authorize('update', $referenceProfile);

        $data = $request->validated();

        $service->revise($identityProposal, $data['persona'] ?? [], $data['avatar'] ?? []);

        return back()->with('status', 'Proposta atualizada. Revise antes de aplicar.');
    }

    public function apply(ReferenceProfile $referenceProfile, IdentityProposal $identityProposal, IdentityProposalService $service): RedirectResponse
    {
        $this->authorize('update', $referenceProfile);

        $wasReady = $identityProposal->isReady();

        $service->apply($identityProposal);

        return back()->with(
            'status',
            $wasReady ? 'Proposta aplicada. Persona e Avatar criados.' : 'Esta proposta já havia sido aplicada.'
        );
    }

    public function discard(ReferenceProfile $referenceProfile, IdentityProposal $identityProposal, IdentityProposalService $service): RedirectResponse
    {
        $this->authorize('update', $referenceProfile);

        $service->discard($identityProposal);

        return back()->with('status', 'Proposta descartada. O histórico foi preservado.');
    }
}
