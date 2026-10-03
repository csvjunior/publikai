<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAvatarRequest;
use App\Http\Requests\UpdateAvatarRequest;
use App\Models\Avatar;
use App\Services\AvatarReferenceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AvatarController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Avatar::class);

        $avatars = Avatar::latest()->paginate(15);

        return view('avatars.index', compact('avatars'));
    }

    public function create(): View
    {
        $this->authorize('create', Avatar::class);

        return view('avatars.create');
    }

    public function store(StoreAvatarRequest $request): RedirectResponse
    {
        $this->authorize('create', Avatar::class);

        $data = $request->validated();

        $this->authorize('updateStatus', [new Avatar, $data['status']]);

        $avatar = Avatar::create($data);

        return redirect()->route('avatars.show', $avatar)->with('status', 'Avatar cadastrado.');
    }

    public function show(Avatar $avatar, AvatarReferenceService $referenceService): View
    {
        $this->authorize('view', $avatar);

        $avatar->load('referenceImages');

        return view('avatars.show', [
            'avatar' => $avatar,
            'maxReferences' => $referenceService->maxReferences(),
        ]);
    }

    public function edit(Avatar $avatar): View
    {
        $this->authorize('update', $avatar);

        return view('avatars.edit', compact('avatar'));
    }

    public function update(UpdateAvatarRequest $request, Avatar $avatar): RedirectResponse
    {
        $this->authorize('update', $avatar);

        $data = $request->validated();

        if (($data['status'] ?? null) !== $avatar->status->value) {
            $this->authorize('updateStatus', [$avatar, $data['status']]);
        }

        $avatar->update($data);

        return redirect()->route('avatars.show', $avatar)->with('status', 'Avatar atualizado.');
    }
}
