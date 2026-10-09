<?php

namespace App\Services;

use App\Enums\ContentType;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;

/**
 * Criação de conteúdo orientada a resultado (Sprint 5.6.4 refactor).
 * Coordena Produto + Persona + Avatar + objetivo → ContentScript interno
 * (roteiro via IA, texto barato). Blueprint resolvido automaticamente
 * (default determinístico); orientação opcional vai para notes.
 * Controller fino; sem nova IA, sem orchestrator (5.6.5).
 */
class ContentCreatorService
{
    public function __construct(protected ContentScriptService $scripts) {}

    public function defaultBlueprint(): ?ContentBlueprint
    {
        return ContentBlueprint::where('status', '!=', 'archived')->orderBy('id')->first();
    }

    /**
     * @return array{script: ContentScript, blueprint: ContentBlueprint}
     */
    public function createVideo(
        Product $product,
        Persona $persona,
        Avatar $avatar,
        string $objective,
        ?string $guidance,
        ?int $blueprintId,
        ?int $createdBy,
    ): array {
        return $this->create(ContentType::Video, $product, $persona, $avatar, $objective, $guidance, $blueprintId, $createdBy);
    }

    /**
     * @return array{script: ContentScript, blueprint: ContentBlueprint}
     */
    public function createImage(
        Product $product,
        Persona $persona,
        Avatar $avatar,
        string $objective,
        ?string $guidance,
        ?int $blueprintId,
        ?int $createdBy,
    ): array {
        return $this->create(ContentType::Image, $product, $persona, $avatar, $objective, $guidance, $blueprintId, $createdBy);
    }

    /**
     * @return array{script: ContentScript, blueprint: ContentBlueprint}
     */
    protected function create(
        ContentType $type,
        Product $product,
        Persona $persona,
        Avatar $avatar,
        string $objective,
        ?string $guidance,
        ?int $blueprintId,
        ?int $createdBy,
    ): array {
        $blueprint = $blueprintId
            ? ContentBlueprint::where('status', '!=', 'archived')->findOrFail($blueprintId)
            : $this->defaultBlueprint();

        abort_unless($blueprint, 422, 'Cadastre um Blueprint antes de criar conteúdo.');

        $script = $this->scripts->generate($product, $blueprint, $persona, $avatar, $createdBy);

        $script->update([
            'content_type' => $type,
            'objective' => $objective,
            'notes' => $guidance !== null && trim($guidance) !== '' ? trim($guidance) : $script->notes,
        ]);

        return ['script' => $script->fresh(), 'blueprint' => $blueprint];
    }

    public function contentTypeOf(ContentScript $script): ContentType
    {
        return $script->resolveContentType();
    }

    public function contentStatusOf(ContentScript $script): string
    {
        return $script->resolveContentStatus();
    }
}
