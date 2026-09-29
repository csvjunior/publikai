<?php

namespace App\Models;

use App\Enums\SocialAccountStatus;
use App\Enums\SocialPlatform;
use Database\Factories\SocialAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conta/perfil social gerenciado pelo Publikai (Sprint 2, cadastro manual).
 * O "Account DNA" (idioma, mercado, nicho, público, tom, estilo, CTA,
 * frequência) orientará o futuro Content Engine. Sem OAuth, sem tokens,
 * sem relacionamentos com módulos inexistentes.
 */
class SocialAccount extends Model
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'platform',
        'username',
        'profile_url',
        'language',
        'market',
        'niche',
        'audience',
        'tone',
        'content_style',
        'default_cta',
        'posting_frequency',
        'default_persona_id',
        'default_avatar_id',
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
            'status' => SocialAccountStatus::class,
        ];
    }

    public function isArchived(): bool
    {
        return $this->status === SocialAccountStatus::Archived;
    }

    /**
     * @return BelongsTo<Persona, $this>
     */
    public function defaultPersona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'default_persona_id');
    }

    /**
     * @return BelongsTo<Avatar, $this>
     */
    public function defaultAvatar(): BelongsTo
    {
        return $this->belongsTo(Avatar::class, 'default_avatar_id');
    }
}
