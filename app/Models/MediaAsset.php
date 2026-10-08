<?php

namespace App\Models;

use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Asset de mídia (Sprint 5.5.0). Arquivo no Storage; banco guarda caminho +
 * metadata segura. Sem base64, sem segredos, sem path absoluto.
 */
class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'source',
        'provider',
        'model',
        'disk',
        'path',
        'filename',
        'mime_type',
        'width',
        'height',
        'duration_seconds',
        'size_bytes',
        'aspect_ratio',
        'status',
        'parent_media_asset_id',
        'created_by',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaAssetType::class,
            'source' => MediaAssetSource::class,
            'status' => MediaAssetStatus::class,
            'metadata' => 'array',
        ];
    }

    public function url(): ?string
    {
        if ($this->disk !== 'public') {
            return null;
        }

        return Storage::disk('public')->url($this->path);
    }

    /**
     * @return BelongsToMany<ContentScript>
     */
    public function contentScripts(): BelongsToMany
    {
        return $this->belongsToMany(ContentScript::class, 'content_script_media_assets')
            ->withPivot(['purpose', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Avatares que usam este asset como referência (Sprint 5.5.3).
     *
     * @return BelongsToMany<Avatar>
     */
    public function referencedByAvatars(): BelongsToMany
    {
        return $this->belongsToMany(Avatar::class, 'avatar_reference_media_assets')
            ->withPivot(['is_primary', 'position'])
            ->withTimestamps();
    }

    /**
     * Asset de origem desta variação (Sprint 5.5.4). Original imutável.
     *
     * @return BelongsTo<MediaAsset, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'parent_media_asset_id');
    }

    /**
     * Variações derivadas deste asset (Sprint 5.5.4).
     *
     * @return HasMany<MediaAsset>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MediaAsset::class, 'parent_media_asset_id');
    }

    public function isVariation(): bool
    {
        return $this->parent_media_asset_id !== null;
    }

    /**
     * Classe de apresentação do player conforme orientação real
     * (Sprint 5.6.0 microajuste). Sem dims: comportamento anterior.
     */
    public function orientationClass(): string
    {
        if (($this->height ?? 0) > ($this->width ?? 0)) {
            return 'mx-auto aspect-[9/16] max-w-44';
        }

        if (($this->width ?? 0) > ($this->height ?? 0)) {
            return 'aspect-video';
        }

        return 'aspect-square';
    }
}
