<?php

namespace App\Policies;

use App\Enums\AvatarStatus;
use App\Models\Avatar;
use App\Models\User;

/**
 * Autorização de avatares (Sprint 3).
 * Mesma regra consolidada: entrar/sair de `archived` só admin.
 */
class AvatarPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Avatar $avatar): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Avatar $avatar): bool
    {
        return true;
    }

    public function updateStatus(User $user, Avatar $avatar, string $targetStatus): bool
    {
        $involvesArchived = $targetStatus === AvatarStatus::Archived->value
            || $avatar->status === AvatarStatus::Archived;

        if (! $involvesArchived) {
            return true;
        }

        return $user->isAdmin();
    }
}
