<?php

namespace App\Models;

use Database\Factories\AffiliateLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Link de afiliado de um produto (Sprint 1).
 * No máximo um link principal por produto (ver AffiliateLinkService).
 */
class AffiliateLink extends Model
{
    /** @use HasFactory<AffiliateLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'label',
        'url',
        'network',
        'market',
        'is_primary',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
