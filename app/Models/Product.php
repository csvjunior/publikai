<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Produto promovido nas operações de afiliados (Sprint 1).
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'product_url',
        'price',
        'currency',
        'market',
        'language',
        'affiliate_network',
        'commission_type',
        'commission_value',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'status' => ProductStatus::class,
        ];
    }

    /**
     * @return HasMany<AffiliateLink, $this>
     */
    public function affiliateLinks(): HasMany
    {
        return $this->hasMany(AffiliateLink::class)->orderByDesc('is_primary')->orderBy('id');
    }

    /**
     * @return HasOne<AffiliateLink, $this>
     */
    public function primaryLink(): HasOne
    {
        return $this->hasOne(AffiliateLink::class)->where('is_primary', true);
    }

    public function isArchived(): bool
    {
        return $this->status === ProductStatus::Archived;
    }
}
