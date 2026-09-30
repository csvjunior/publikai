<?php

namespace App\Services;

use App\AI\Exceptions\AiProviderException;
use App\AI\Schemas\ContentScriptSchema;
use App\Enums\ContentScriptSource;
use App\Enums\ContentScriptStatus;
use App\Exceptions\ConflictingScriptContextException;
use App\Models\Avatar;
use App\Models\ContentBlueprint;
use App\Models\ContentScript;
use App\Models\Persona;
use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Roteiros manuais e por IA (Sprint 5.4).
 * Contexto: Product (base de idioma/mercado) + Blueprint + Persona + Avatar.
 * Conflito forte de language/market rejeita a geração; IA falha vira failed
 * sanitizado sem quebrar a página. Revisão só em draft/ready; aprovação só
 * de ready; sem reabertura nesta Sprint.
 */
class ContentScriptService
{
    public function __construct(protected AiService $ai) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createManual(array $data, ?int $createdBy = null): ContentScript
    {
        $resolved = $this->resolveLocale(
            Product::findOrFail($data['product_id']),
            Persona::findOrFail($data['persona_id']),
            ContentBlueprint::findOrFail($data['content_blueprint_id']),
            Avatar::findOrFail($data['avatar_id']),
        );

        return ContentScript::create(array_merge($data, [
            'slug' => $this->uniqueSlug($data['title']),
            'status' => ContentScriptStatus::Draft,
            'generation_source' => ContentScriptSource::Manual,
            'language' => $resolved['language'],
            'market' => $resolved['market'],
            'created_by' => $createdBy,
            'started_at' => now(),
            'completed_at' => now(),
        ]));
    }

    /**
     * @throws ConflictingScriptContextException
     */
    public function generate(Product $product, ContentBlueprint $blueprint, Persona $persona, Avatar $avatar, ?int $createdBy = null): ContentScript
    {
        $resolved = $this->resolveLocale($product, $persona, $blueprint, $avatar);

        $base = [
            'product_id' => $product->id,
            'content_blueprint_id' => $blueprint->id,
            'persona_id' => $persona->id,
            'avatar_id' => $avatar->id,
            'created_by' => $createdBy,
            'title' => 'Roteiro '.$product->name.' — '.$blueprint->name,
            'slug' => $this->uniqueSlug($product->name.' '.$blueprint->name),
            'language' => $resolved['language'],
            'market' => $resolved['market'],
            'generation_source' => ContentScriptSource::Ai,
            'provider' => config('ai.provider', 'google'),
            'model' => (string) config('ai.google.model'),
            'started_at' => now(),
        ];

        try {
            $result = $this->ai->generate(
                operation: 'content_script',
                instructions: ContentScriptSchema::instructions(),
                input: $this->buildInput($product, $blueprint, $persona, $avatar, $resolved),
                schema: ContentScriptSchema::schema(),
            );
        } catch (AiProviderException $e) {
            return ContentScript::create(array_merge($base, [
                'status' => ContentScriptStatus::Failed,
                'error_code' => $e->errorCode,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]));
        }

        $data = $this->validatedData($result->data);

        if ($data === null) {
            return ContentScript::create(array_merge($base, [
                'status' => ContentScriptStatus::Failed,
                'error_code' => 'schema_mismatch',
                'error_message' => 'Resposta da IA fora do formato esperado.',
                'completed_at' => now(),
            ]));
        }

        return ContentScript::create(array_merge($base, $data, [
            'status' => ContentScriptStatus::Ready,
            'completed_at' => now(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function revise(ContentScript $script, array $data): ContentScript
    {
        abort_unless($script->isEditable(), 409, 'Roteiro aprovado ou arquivado não pode ser editado.');

        $script->update($data);

        return $script;
    }

    public function markReady(ContentScript $script): ContentScript
    {
        abort_unless($script->status === ContentScriptStatus::Draft, 409, 'Somente rascunhos podem ser marcados como prontos.');

        $script->update(['status' => ContentScriptStatus::Ready]);

        return $script;
    }

    public function approve(ContentScript $script): ContentScript
    {
        abort_unless($script->isReady(), 409, 'Somente roteiros prontos podem ser aprovados.');

        $script->update([
            'status' => ContentScriptStatus::Approved,
            'approved_at' => now(),
        ]);

        return $script;
    }

    /**
     * Idioma/mercado: Product como base; demais quando coerentes.
     * Conflito forte (valores distintos preenchidos) rejeita a operação.
     *
     * @return array{language: ?string, market: ?string}
     *
     * @throws ConflictingScriptContextException
     */
    protected function resolveLocale(Product $product, Persona $persona, ContentBlueprint $blueprint, Avatar $avatar): array
    {
        $languages = array_values(array_unique(array_filter([
            $product->language, $persona->language, $blueprint->language, $avatar->language,
        ])));
        $markets = array_values(array_unique(array_filter([
            $product->market, $persona->market, $blueprint->market, $avatar->market,
        ])));

        if (count($languages) > 1) {
            throw new ConflictingScriptContextException(
                'Idiomas divergentes entre produto, persona, blueprint e avatar ('.implode(', ', $languages).').'
            );
        }

        if (count($markets) > 1) {
            throw new ConflictingScriptContextException(
                'Mercados divergentes entre produto, persona, blueprint e avatar ('.implode(', ', $markets).').'
            );
        }

        return [
            'language' => $languages[0] ?? null,
            'market' => $markets[0] ?? null,
        ];
    }

    protected function buildInput(Product $product, ContentBlueprint $blueprint, Persona $persona, Avatar $avatar, array $resolved): string
    {
        $lines = ['SCRIPT CONTEXT'];

        foreach ([
            'Product' => $product->name,
            'Product description' => $product->description,
            'Category' => $product->category,
            'Price' => $product->price ? $product->currency.' '.$product->price : null,
            'Network' => $product->affiliate_network,
            'Product notes' => $product->notes,
        ] as $label => $value) {
            if ($value) {
                $lines[] = $label.': '.$value;
            }
        }

        foreach ([
            'Blueprint' => $blueprint->name,
            'Content type' => $blueprint->content_type,
            'Objective' => $blueprint->objective,
            'Hook pattern' => $blueprint->hook_pattern,
            'Structure' => $blueprint->structure_pattern,
            'CTA pattern' => $blueprint->cta_pattern,
            'Visual style' => $blueprint->visual_style,
            'Communication style' => $blueprint->communication_style,
            'Target duration' => $blueprint->recommended_duration_seconds ? $blueprint->recommended_duration_seconds.'s' : null,
            'Persona' => $persona->name.' — '.$persona->tone.', '.$persona->communication_style,
            'Persona audience' => $persona->audience,
            'Avatar' => $avatar->name.' — '.$avatar->visual_style,
            'Language' => $resolved['language'],
            'Market' => $resolved['market'],
        ] as $label => $value) {
            if ($value) {
                $lines[] = $label.': '.$value;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function validatedData(array $data): ?array
    {
        foreach (['title', 'hook', 'body', 'cta'] as $field) {
            if (! isset($data[$field]) || ! is_string($data[$field]) || trim($data[$field]) === '') {
                return null;
            }
        }

        if (! isset($data['duration_seconds']) || ! is_int($data['duration_seconds']) || $data['duration_seconds'] < 1) {
            return null;
        }

        $validated = [
            'title' => $data['title'],
            'hook' => $data['hook'],
            'body' => $data['body'],
            'cta' => $data['cta'],
            'duration_seconds' => $data['duration_seconds'],
        ];

        foreach (['opening', 'on_screen_text', 'visual_direction', 'voice_direction'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && ! is_string($data[$field])) {
                return null;
            }

            $validated[$field] = $data[$field] ?? null;
        }

        return $validated;
    }

    protected function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'roteiro';
        $slug = $base;
        $counter = 2;

        while (ContentScript::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
