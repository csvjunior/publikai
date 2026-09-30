<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentBlueprintRequest;
use App\Http\Requests\UpdateContentBlueprintRequest;
use App\Models\ContentBlueprint;
use App\Services\ContentBlueprintService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentBlueprintController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', ContentBlueprint::class);

        $blueprints = ContentBlueprint::latest()->paginate(15);

        return view('blueprints.index', compact('blueprints'));
    }

    public function create(): View
    {
        $this->authorize('create', ContentBlueprint::class);

        return view('blueprints.create');
    }

    public function store(StoreContentBlueprintRequest $request, ContentBlueprintService $service): RedirectResponse
    {
        $this->authorize('create', ContentBlueprint::class);

        $data = $request->validated();

        $this->authorize('updateStatus', [new ContentBlueprint, $data['status']]);

        $blueprint = $service->create($data);

        return redirect()->route('blueprints.show', $blueprint)->with('status', 'Blueprint cadastrado.');
    }

    public function show(ContentBlueprint $contentBlueprint): View
    {
        $this->authorize('view', $contentBlueprint);

        return view('blueprints.show', ['blueprint' => $contentBlueprint]);
    }

    public function edit(ContentBlueprint $contentBlueprint): View
    {
        $this->authorize('update', $contentBlueprint);

        return view('blueprints.edit', ['blueprint' => $contentBlueprint]);
    }

    public function update(UpdateContentBlueprintRequest $request, ContentBlueprint $contentBlueprint, ContentBlueprintService $service): RedirectResponse
    {
        $this->authorize('update', $contentBlueprint);

        $data = $request->validated();

        if (($data['status'] ?? null) !== $contentBlueprint->status->value) {
            $this->authorize('updateStatus', [$contentBlueprint, $data['status']]);
        }

        $service->update($contentBlueprint, $data);

        return redirect()->route('blueprints.show', $contentBlueprint)->with('status', 'Blueprint atualizado.');
    }
}
