<?php

namespace App\Models;

use App\Enums\AudioVideoDurationPolicy;
use App\Enums\AudioVideoMergeRequestStatus;
use Database\Factories\AudioVideoMergeRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Merge local vídeo + narração (Sprint 5.6.3, FFmpeg).
 * Snapshot de vídeo + áudio; output ligado após success. Proveniência
 * oficial: os três IDs neste request (sem parent único).
 */
class AudioVideoMergeRequest extends Model
{
    /** @use HasFactory<AudioVideoMergeRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'duration_policy',
        'content_script_id',
        'video_media_asset_id',
        'audio_media_asset_id',
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
            'status' => AudioVideoMergeRequestStatus::class,
            'duration_policy' => AudioVideoDurationPolicy::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'video_media_asset_id');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function audio(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'audio_media_asset_id');
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
