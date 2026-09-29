<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReferenceContentRequest;
use App\Models\ReferenceContent;
use App\Models\ReferenceProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ReferenceContentController extends Controller
{
    use AuthorizesRequests;

    public function store(ReferenceContentRequest $request, ReferenceProfile $referenceProfile): RedirectResponse
    {
        $this->authorize('create', ReferenceContent::class);

        $data = $request->validated();

        $this->authorize('updateStatus', [new ReferenceContent, $data['status']]);

        $referenceProfile->referenceContents()->create($data);

        return back()->with('status', 'Conteúdo de referência adicionado.');
    }

    public function update(ReferenceContentRequest $request, ReferenceProfile $referenceProfile, ReferenceContent $referenceContent): RedirectResponse
    {
        $this->authorize('update', $referenceContent);

        $data = $request->validated();

        if (($data['status'] ?? null) !== $referenceContent->status->value) {
            $this->authorize('updateStatus', [$referenceContent, $data['status']]);
        }

        $referenceContent->update($data);

        return back()->with('status', 'Conteúdo de referência atualizado.');
    }
}
