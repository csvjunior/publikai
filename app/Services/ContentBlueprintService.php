<?php

namespace App\Services;

use App\Models\ContentBlueprint;
use Illuminate\Support\Str;

/**
 * Regras de blueprint (Sprint 5.3): slug único + source manual automática.
 * Espelho do ProductService; ponto de extensão para o futuro AI-assisted.
 */
class ContentBlueprintService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ContentBlueprint
    {
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['source_type'] = 'manual';

        return ContentBlueprint::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ContentBlueprint $blueprint, array $data): ContentBlueprint
    {
        if (($data['name'] ?? $blueprint->name) !== $blueprint->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $blueprint->id);
        }

        $blueprint->update($data);

        return $blueprint;
    }

    protected function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'blueprint';
        $slug = $base;
        $counter = 2;

        while (ContentBlueprint::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
