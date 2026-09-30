<?php

namespace App\Http\Controllers;

use App\Enums\IdentityProposalStatus;
use App\Enums\ReferenceAnalysisStatus;
use App\Http\Requests\StoreReferenceProfileRequest;
use App\Http\Requests\UpdateReferenceProfileRequest;
use App\Models\ReferenceProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReferenceProfileController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', ReferenceProfile::class);

        $profiles = ReferenceProfile::withCount('referenceContents')->latest()->paginate(15);

        return view('references.index', compact('profiles'));
    }

    public function create(): View
    {
        $this->authorize('create', ReferenceProfile::class);

        return view('references.create');
    }

    public function store(StoreReferenceProfileRequest $request): RedirectResponse
    {
        $this->authorize('create', ReferenceProfile::class);

        $data = $request->validated();

        $this->authorize('updateStatus', [new ReferenceProfile, $data['status']]);

        $profile = ReferenceProfile::create($data);

        return redirect()->route('references.show', $profile)->with('status', 'Referência cadastrada.');
    }

    public function show(ReferenceProfile $referenceProfile): View
    {
        $this->authorize('view', $referenceProfile);

        $referenceProfile->load('referenceContents');

        $analyses = $referenceProfile->referenceAnalyses()->latest()->get();
        $featured = $analyses->firstWhere('status', ReferenceAnalysisStatus::Success) ?? $analyses->first();

        $proposals = $referenceProfile->identityProposals()->latest()->get();
        $featuredProposal = $proposals->firstWhere('status', IdentityProposalStatus::Ready)
            ?? $proposals->firstWhere('status', IdentityProposalStatus::Applied)
            ?? $proposals->first();

        $hasSuccessfulAnalysis = $referenceProfile->referenceAnalyses()
            ->where('status', ReferenceAnalysisStatus::Success)
            ->exists();

        return view('references.show', [
            'profile' => $referenceProfile,
            'analyses' => $analyses,
            'featuredAnalysis' => $featured,
            'proposals' => $proposals,
            'featuredProposal' => $featuredProposal,
            'hasSuccessfulAnalysis' => $hasSuccessfulAnalysis,
            'aiConfigured' => (bool) config('ai.google.enabled') && (string) config('ai.google.auth_key') !== '',
        ]);
    }

    public function edit(ReferenceProfile $referenceProfile): View
    {
        $this->authorize('update', $referenceProfile);

        return view('references.edit', ['profile' => $referenceProfile]);
    }

    public function update(UpdateReferenceProfileRequest $request, ReferenceProfile $referenceProfile): RedirectResponse
    {
        $this->authorize('update', $referenceProfile);

        $data = $request->validated();

        if (($data['status'] ?? null) !== $referenceProfile->status->value) {
            $this->authorize('updateStatus', [$referenceProfile, $data['status']]);
        }

        $referenceProfile->update($data);

        return redirect()->route('references.show', $referenceProfile)->with('status', 'Referência atualizada.');
    }
}
