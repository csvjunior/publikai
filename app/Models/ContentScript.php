<?php

namespace App\Models;

use App\Enums\ContentScriptSource;
use App\Enums\ContentScriptStatus;
use Database\Factories\ContentScriptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Roteiro textual estruturado (Sprint 5.4): manual ou por IA, revisável e
 * aprovável. Não é mídia, publicação ou Campaign. CTA textual, sem links.
 */
class ContentScript extends Model
{
    /** @use HasFactory<ContentScriptFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'content_blueprint_id',
        'persona_id',
        'avatar_id',
        'created_by',
        'title',
        'slug',
        'status',
        'language',
        'market',
        'objective',
        'hook',
        'opening',
        'body',
        'cta',
        'on_screen_text',
        'visual_direction',
        'voice_direction',
        'duration_seconds',
        'generation_source',
        'provider',
        'model',
        'error_code',
        'error_message',
        'started_at',
        'completed_at',
        'approved_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentScriptStatus::class,
            'generation_source' => ContentScriptSource::class,
            'duration_seconds' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ContentBlueprint, $this>
     */
    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(ContentBlueprint::class, 'content_blueprint_id');
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * @return BelongsTo<Avatar, $this>
     */
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Avatar::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ImageGenerationRequest>
     */
    public function imageRequests(): HasMany
    {
        return $this->hasMany(ImageGenerationRequest::class, 'content_script_id')->latest();
    }

    /**
     * @return BelongsToMany<MediaAsset>
     */
    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'content_script_media_assets')
            ->withPivot(['purpose', 'is_primary'])
            ->withTimestamps()
            ->orderByDesc('content_script_media_assets.created_at');
    }

    public function isReady(): bool
    {
        return $this->status === ContentScriptStatus::Ready;
    }

    public function isArchived(): bool
    {
        return $this->status === ContentScriptStatus::Archived;
    }

    public function isApproved(): bool
    {
        return $this->status === ContentScriptStatus::Approved;
    }

    public function isFailed(): bool
    {
        return $this->status === ContentScriptStatus::Failed;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [ContentScriptStatus::Draft, ContentScriptStatus::Ready], true);
    }
}
