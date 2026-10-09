<?php

namespace App\Http\Controllers;

use App\Enums\ContentScriptStatus;
use App\Enums\ContentType;
use App\Http\Requests\StoreContentRequest;
use App\Jobs\RunContentProductionJob;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;
use App\Services\ContentCreatorService;
use App\Services\ContentProductionService;
use App\Services\ContentStatusResolver;
use App\Services\ProductionFlowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentController extends Controller
{
    use AuthorizesRequests;

    public function index(ContentStatusResolver $statuses): View
    {
        $this->authorize('viewAny', ContentScript::class);

        $scripts = ContentScript::with(['product', 'persona', 'avatar', 'productions'])
            ->latest()
            ->paginate(15);

        return view('content.index', compact('scripts', 'statuses'));
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
        ContentStatusResolver $statuses,
    ): View {
        $this->authorize('view', $content);

        $content->load(['product', 'blueprint', 'persona', 'avatar', 'mediaAssets', 'productions']);

        return view('content.show', [
            'script' => $content,
            'type' => $creator->contentTypeOf($content),
            'statusLabel' => $statuses->for($content),
            'flow' => $flow->for($content),
            'production' => $content->productions->sortByDesc('id')->first(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function options(string $model): array
    {
        return $model::where('status', '!=', 'archived')->orderBy('name')->pluck('name', 'id')->all();
    }

    public function produce(
        ContentScript $content,
        ContentProductionService $productions,
    ): RedirectResponse {
        $this->authorize('update', $content);

        abort_unless(
            in_array($content->status, [ContentScriptStatus::Ready, ContentScriptStatus::Approved], true),
            403,
            'Produção só para roteiros prontos ou aprovados.'
        );

        $result = $productions->start($content, (bool) request()->boolean('force_new'), auth()->id());

        RunContentProductionJob::dispatch($result['production']->id);

        return redirect()->route('content.show', ['content' => $content])->with(
            'status',
            $result['created'] ? 'Produção iniciada.' : (($result['resumed'] ?? false) ? 'Continuando de onde parou.' : 'Seu vídeo já está sendo produzido.')
        );
    }
}
