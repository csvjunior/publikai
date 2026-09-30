<?php

namespace App\Models;

use App\Enums\ReferenceProfileStatus;
use App\Enums\SocialPlatform;
use Database\Factories\ReferenceProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Perfil/canal externo usado como referência (Sprint 4).
 * Contexto por language/market/niche; sem vínculo com Product nesta Sprint
 * (associação explícita entra com o Content Engine). Sem scraping, sem IA.
 */
class ReferenceProfile extends Model
{
    /** @use HasFactory<ReferenceProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'platform',
        'username',
        'profile_url',
        'language',
        'market',
        'niche',
        'reason',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'status' => ReferenceProfileStatus::class,
        ];
    }

    /**
     * @return HasMany<ReferenceContent, $this>
     */
    public function referenceContents(): HasMany
    {
        return $this->hasMany(ReferenceContent::class)->orderBy('id');
    }

    /**
     * @return HasMany<ReferenceAnalysis, $this>
     */
    public function referenceAnalyses(): HasMany
    {
        return $this->hasMany(ReferenceAnalysis::class)->latest();
    }

    public function isArchived(): bool
    {
        return $this->status === ReferenceProfileStatus::Archived;
    }
}
