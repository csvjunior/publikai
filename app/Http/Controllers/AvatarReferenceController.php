<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAvatarReferenceRequest;
use App\Models\Avatar;
use App\Models\MediaAsset;
use App\Services\AvatarReferenceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Referências visuais do Avatar (Sprints 5.5.2/5.5.3).
 * Upload manual aprovado pela equipe → consistência visual do personagem
 * artificial. Autorização segue a policy do Avatar (sem RBAC novo).
 */
class AvatarReferenceController extends Controller
{
    use AuthorizesRequests;

    public function create(Avatar $avatar, AvatarReferenceService $service): View
    {
        $this->authorize('update', $avatar);

        $avatar->load('referenceImages');

        return view('avatars.reference.create', [
            'avatar' => $avatar,
            'atLimit' => $avatar->referenceImages->count() >= $service->maxReferences(),
            'maxReferences' => $service->maxReferences(),
        ]);
    }

    public function store(
        StoreAvatarReferenceRequest $request,
        Avatar $avatar,
        AvatarReferenceService $service,
    ): RedirectResponse {
        $this->authorize('update', $avatar);

        $service->attach($avatar, $request->file('image'), auth()->id());

        return redirect()->route('avatars.show', $avatar)->with(
            'status',
            'Referência adicionada.'
        );
    }

    public function destroy(Avatar $avatar, MediaAsset $mediaAsset, AvatarReferenceService $service): RedirectResponse
    {
        $this->authorize('update', $avatar);

        $service->remove($avatar, $mediaAsset);

        return redirect()->route('avatars.show', $avatar)->with(
            'status',
            'Referência removida.'
        );
    }

    public function markPrimary(Avatar $avatar, MediaAsset $mediaAsset, AvatarReferenceService $service): RedirectResponse
    {
        $this->authorize('update', $avatar);

        $service->markPrimary($avatar, $mediaAsset);

        return redirect()->route('avatars.show', $avatar)->with(
            'status',
            'Referência definida como principal.'
        );
    }
}
