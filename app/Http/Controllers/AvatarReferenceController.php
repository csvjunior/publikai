<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAvatarReferenceRequest;
use App\Models\Avatar;
use App\Services\AvatarReferenceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Imagem de referência do Avatar (Sprint 5.5.2).
 * Upload manual aprovado pela equipe → consistência visual do personagem
 * artificial. Autorização segue a policy do Avatar (sem RBAC novo).
 */
class AvatarReferenceController extends Controller
{
    use AuthorizesRequests;

    public function create(Avatar $avatar): View
    {
        $this->authorize('update', $avatar);

        $avatar->load('referenceImage');

        return view('avatars.reference.create', compact('avatar'));
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
            'Imagem de referência salva.'
        );
    }

    public function destroy(Avatar $avatar, AvatarReferenceService $service): RedirectResponse
    {
        $this->authorize('update', $avatar);

        $service->detach($avatar);

        return redirect()->route('avatars.show', $avatar)->with(
            'status',
            'Imagem de referência removida.'
        );
    }
}
