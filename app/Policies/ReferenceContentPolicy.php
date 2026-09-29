<?php

namespace App\Policies;

use App\Enums\ReferenceContentStatus;
use App\Models\ReferenceContent;
use App\Models\User;

/**
 * Autorização de conteúdos de referência (Sprint 4).
 * Mesma regra consolidada: entrar/sair de `archived` só admin.
 */
class ReferenceContentPolicy
{
    public function view(User $user, ReferenceContent $referenceContent): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ReferenceContent $referenceContent): bool
    {
        return true;
    }

    public function updateStatus(User $user, ReferenceContent $referenceContent, string $targetStatus): bool
    {
        $involvesArchived = $targetStatus === ReferenceContentStatus::Archived->value
            || $referenceContent->status === ReferenceContentStatus::Archived;

        if (! $involvesArchived) {
            return true;
        }

        return $user->isAdmin();
    }
}
