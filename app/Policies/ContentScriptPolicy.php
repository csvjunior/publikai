<?php

namespace App\Policies;

use App\Enums\ContentScriptStatus;
use App\Models\ContentScript;
use App\Models\User;

/**
 * Autorização de roteiros (Sprint 5.4).
 * Mesma regra consolidada: entrar/sair de `archived` só admin.
 */
class ContentScriptPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ContentScript $script): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ContentScript $script): bool
    {
        return true;
    }

    public function updateStatus(User $user, ContentScript $script, string $targetStatus): bool
    {
        $involvesArchived = $targetStatus === ContentScriptStatus::Archived->value
            || $script->status === ContentScriptStatus::Archived;

        if (! $involvesArchived) {
            return true;
        }

        return $user->isAdmin();
    }
}
