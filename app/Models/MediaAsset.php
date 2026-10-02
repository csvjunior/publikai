<?php

namespace App\Models;

use App\Enums\MediaAssetSource;
use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
