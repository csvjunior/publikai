<?php

namespace App\Services;

use App\Models\AffiliateLink;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Regra de link principal (Sprint 1): no máximo um AffiliateLink principal
 * por produto. Quando um link passa a ser principal, os demais do mesmo
 * produto deixam de ser — em transação.
 */
class AffiliateLinkService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Product $product, array $data): AffiliateLink
    {
        return DB::transaction(function () use ($product, $data) {
            if (! empty($data['is_primary'])) {
                $product->affiliateLinks()->update(['is_primary' => false]);
            }

            return $product->affiliateLinks()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AffiliateLink $link, array $data): AffiliateLink
    {
        DB::transaction(function () use ($link, $data) {
            if (! empty($data['is_primary'])) {
                $link->product->affiliateLinks()->where('id', '!=', $link->id)->update(['is_primary' => false]);
            }

            $link->update($data);
        });

        return $link;
    }
}
