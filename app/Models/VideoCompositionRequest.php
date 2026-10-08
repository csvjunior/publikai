<?php

namespace App\Models;

use App\Enums\VideoCompositionRequestStatus;
use Database\Factories\VideoCompositionRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Composição local de vídeo (Sprint 5.6.1, FFmpeg).
 * Inputs com snapshot (ordem, trim, duração); output ligado após success.
 * Proveniência multi-input vive nos inputs — sem grafo genérico.
 */
class VideoCompositionRequest extends Model
{
    /** @use HasFactory<VideoCompositionRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'content_script_id',
        'output_media_asset_id',
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
            'status' => VideoCompositionRequestStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<VideoCompositionInput>
     */
    public function inputs(): HasMany
    {
        return $this->hasMany(VideoCompositionInput::class, 'video_composition_request_id')->orderBy('position');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function output(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'output_media_asset_id');
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
