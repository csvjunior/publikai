<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Regras de produto (Sprint 1): geração de slug único.
 * Arquivamento é mudança de status (sem delete físico), com autorização
 * restrita a admin via ProductPolicy@archive.
 */
class ProductService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        $data['slug'] = $this->uniqueSlug($data['name']);

        return Product::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        if (($data['name'] ?? $product->name) !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $product->update($data);

        return $product;
    }

    protected function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produto';
        $slug = $base;
        $counter = 2;

        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
