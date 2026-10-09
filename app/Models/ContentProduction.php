<?php

namespace App\Models;

use App\Enums\ContentProductionStatus;
use App\Enums\ContentProductionStep;
use Database\Factories\ContentProductionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Execução coordenada de produção (Sprint 5.6.5, orchestrator).
 * UMA ação do usuário; etapas internas com reuse-first; requests filhos
 * linkados por content_production_id (nullable, fluxos vivem sozinhos).
 */
class ContentProduction extends Model
{
    /** @use HasFactory<ContentProductionFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
        'current_step',
        'force_new',
        'content_script_id',
        'image_media_asset_id',
        'video_media_asset_id',
        'audio_media_asset_id',
        'final_media_asset_id',
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
            'status' => ContentProductionStatus::class,
            'current_step' => ContentProductionStep::class,
            'force_new' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ContentScript, $this>
     */
    public function script(): BelongsTo
    {
        return $this->belongsTo(ContentScript::class, 'content_script_id');
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function isActive(): bool
    {
        return in_array($this->status, [ContentProductionStatus::Pending, ContentProductionStatus::Processing], true);
    }

    public function isWaiting(): bool
    {
        return $this->status === ContentProductionStatus::Pending;
    }

    public function isFailed(): bool
    {
        return $this->status === ContentProductionStatus::Failed;
    }

    public function isSuccess(): bool
    {
        return $this->status === ContentProductionStatus::Success;
    }

    /**
     * Subtexto amigável da falha por etapa (revisão visual 5.6.5).
     * Nunca expõe error_code, provider ou HTTP.
     */
    public function failureHint(): ?string
    {
        if ($this->status !== ContentProductionStatus::Failed) {
            return null;
        }

        return match ($this->current_step) {
            ContentProductionStep::Preparing, ContentProductionStep::Image => 'Falhou ao preparar o visual.',
            ContentProductionStep::Video => 'Falhou ao criar o vídeo.',
            ContentProductionStep::Audio => 'Falhou ao criar a narração.',
            ContentProductionStep::Finalizing => 'Falhou ao finalizar o vídeo.',
            ContentProductionStep::Completed => null,
        };
    }

    /**
     * @return array<int, array{key: string, label: string, state: string}>
     */
    public function stepStates(): array
    {
        $steps = [
            'preparing' => 'Preparando',
            'image' => 'Preparando visual',
            'video' => 'Criando vídeo',
            'audio' => 'Criando narração',
            'finalizing' => 'Finalizando vídeo',
        ];

        $order = array_keys($steps);
        $current = array_search($this->current_step->value, $order, true);
        $current = $current === false ? 0 : $current;
        $failed = $this->status === ContentProductionStatus::Failed;

        $states = [];

        foreach ($order as $position => $key) {
            $states[] = [
                'key' => $key,
                'label' => $steps[$key],
                'state' => $position < $current ? 'done' : ($position === $current ? ($failed ? 'failed' : 'current') : 'todo'),
            ];
        }

        return $states;
    }
}
