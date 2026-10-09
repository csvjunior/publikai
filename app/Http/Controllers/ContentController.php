<?php

namespace App\Http\Controllers;

use App\Enums\ContentType;
use App\Http\Requests\StoreContentRequest;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;
use App\Services\ContentCreatorService;
use App\Services\ProductionFlowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', ContentScript::class);

        $scripts = ContentScript::with(['product', 'persona', 'avatar'])
            ->latest()
            ->paginate(15);

        return view('content.index', compact('scripts'));
    }

    public function create(ContentCreatorService $creator): View
    {
        $this->authorize('create', ContentScript::class);

        $type = request()->query('type') === 'image' ? ContentType::Image : ContentType::Video;

        return view('content.create', [
            'type' => $type,
            'productOptions' => $this->options(Product::class),
            'personaOptions' => $this->options(Persona::class),
            'avatarOptions' => $this->options(Avatar::class),
            'blueprintOptions' => $this->options(ContentBlueprint::class),
            'defaultBlueprint' => $creator->defaultBlueprint(),
        ]);
    }

    public function store(StoreContentRequest $request, ContentCreatorService $creator): RedirectResponse
    {
        $this->authorize('create', ContentScript::class);

        $data = $request->validated();

        $product = Product::where('status', '!=', 'archived')->findOrFail($data['product_id']);
        $persona = Persona::where('status', '!=', 'archived')->findOrFail($data['persona_id']);
        $avatar = Avatar::where('status', '!=', 'archived')->findOrFail($data['avatar_id']);

        $result = $data['type'] === 'image'
            ? $creator->createImage($product, $persona, $avatar, $data['objective'], $data['guidance'] ?? null, $data['content_blueprint_id'] ?? null, auth()->id())
            : $creator->createVideo($product, $persona, $avatar, $data['objective'], $data['guidance'] ?? null, $data['content_blueprint_id'] ?? null, auth()->id());

        return redirect()->route('content.show', ['content' => $result['script']->id])->with(
            'status',
            $result['script']->isFailed() ? 'A IA não conseguiu gerar agora. Veja o erro abaixo.' : 'Roteiro gerado. Revise abaixo.'
        );
    }

    public function show(
        ContentScript $content,
        ContentCreatorService $creator,
        ProductionFlowService $flow,
    ): View {
        $this->authorize('view', $content);

        $content->load(['product', 'blueprint', 'persona', 'avatar', 'mediaAssets']);

        return view('content.show', [
            'script' => $content,
            'type' => $creator->contentTypeOf($content),
            'statusLabel' => $creator->contentStatusOf($content),
            'flow' => $flow->for($content),
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function options(string $model): array
    {
        return $model::where('status', '!=', 'archived')->orderBy('name')->pluck('name', 'id')->all();
    }
}
