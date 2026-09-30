<?php

namespace App\Http\Controllers;

use App\Exceptions\ConflictingScriptContextException;
use App\Http\Requests\GenerateContentScriptRequest;
use App\Http\Requests\StoreContentScriptRequest;
use App\Http\Requests\UpdateContentScriptRequest;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;
use App\Services\ContentScriptService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentScriptController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', ContentScript::class);

        $scripts = ContentScript::with(['product', 'blueprint', 'persona'])->latest()->paginate(15);

        return view('scripts.index', compact('scripts'));
    }

    public function create(): View
    {
        $this->authorize('create', ContentScript::class);

        return view('scripts.create', $this->contextOptions());
    }

    public function store(StoreContentScriptRequest $request, ContentScriptService $service): RedirectResponse
    {
        $this->authorize('create', ContentScript::class);

        $data = $request->validated();

        $this->authorize('updateStatus', [new ContentScript, $data['status']]);

        try {
            $script = $service->createManual($data, auth()->id());
        } catch (ConflictingScriptContextException $e) {
            return back()->withInput()->with('script_notice', $e->getMessage());
        }

        return redirect()->route('scripts.show', $script)->with('status', 'Roteiro cadastrado como rascunho.');
    }

    public function generate(GenerateContentScriptRequest $request, ContentScriptService $service): RedirectResponse
    {
        $this->authorize('create', ContentScript::class);

        $data = $request->validated();

        try {
            $script = $service->generate(
                Product::findOrFail($data['product_id']),
                ContentBlueprint::findOrFail($data['content_blueprint_id']),
                Persona::findOrFail($data['persona_id']),
                Avatar::findOrFail($data['avatar_id']),
                auth()->id(),
            );
        } catch (ConflictingScriptContextException $e) {
            return back()->withInput()->with('script_notice', $e->getMessage());
        }

        return redirect()->route('scripts.show', $script)->with(
            'status',
            $script->isFailed() ? 'A IA não conseguiu gerar agora. Veja o erro abaixo.' : 'Roteiro gerado. Revise abaixo.'
        );
    }

    public function show(ContentScript $contentScript): View
    {
        $this->authorize('view', $contentScript);

        $contentScript->load(['product', 'blueprint', 'persona', 'avatar']);

        return view('scripts.show', ['script' => $contentScript]);
    }

    public function edit(ContentScript $contentScript): View
    {
        $this->authorize('update', $contentScript);

        return view('scripts.edit', array_merge(
            ['script' => $contentScript, 'editable' => $contentScript->isEditable()],
            $this->contextOptions()
        ));
    }

    public function update(UpdateContentScriptRequest $request, ContentScript $contentScript, ContentScriptService $service): RedirectResponse
    {
        $this->authorize('update', $contentScript);

        $data = $request->validated();

        if (($data['status'] ?? null) !== $contentScript->status->value) {
            $this->authorize('updateStatus', [$contentScript, $data['status']]);
        }

        $service->revise($contentScript, $data);

        return redirect()->route('scripts.show', $contentScript)->with('status', 'Roteiro atualizado.');
    }

    public function ready(ContentScript $contentScript, ContentScriptService $service): RedirectResponse
    {
        $this->authorize('update', $contentScript);

        $service->markReady($contentScript);

        return back()->with('status', 'Roteiro marcado como pronto.');
    }

    public function approve(ContentScript $contentScript, ContentScriptService $service): RedirectResponse
    {
        $this->authorize('update', $contentScript);

        $service->approve($contentScript);

        return back()->with('status', 'Roteiro aprovado.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function contextOptions(): array
    {
        $available = fn (string $model) => $model::where('status', '!=', 'archived')->orderBy('name')->pluck('name', 'id')->all();

        return [
            'productOptions' => $available(Product::class),
            'blueprintOptions' => $available(ContentBlueprint::class),
            'personaOptions' => $available(Persona::class),
            'avatarOptions' => $available(Avatar::class),
        ];
    }
}
