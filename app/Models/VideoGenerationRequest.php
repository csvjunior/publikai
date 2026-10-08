<?php

namespace App\Models;

use App\Enums\VideoGenerationRequestStatus;
use Database\Factories\VideoGenerationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitação de geração de vídeo image-to-video (Sprint 5.6.0, Omni).
 * Guarda prompt + source snapshot (necessários ao Job/polling) — nunca
 * copiados para logs ou ai_generations. MediaAsset só existe após success.
 */
class VideoGenerationRequest extends Model
{
    /** @use HasFactory<VideoGenerationRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'prompt',
        'aspect_ratio',
        'duration_seconds',
        'provider',
        'model',
        'content_script_id',
        'source_media_asset_id',
        'media_asset_id',
        'operation_external_id',
        'error_code',
        'error_message',
        'created_by',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VideoGenerationRequestStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function sourceImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'source_media_asset_id');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
