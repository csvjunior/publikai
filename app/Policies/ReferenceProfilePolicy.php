<?php

namespace App\Policies;

use App\Enums\ReferenceProfileStatus;
use App\Models\ReferenceProfile;
use App\Models\User;

/**
 * Autorização de perfis de referência (Sprint 4).
 * Mesma regra consolidada: entrar/sair de `archived` só admin.
 */
class ReferenceProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReferenceProfile $referenceProfile): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ReferenceProfile $referenceProfile): bool
    {
        return true;
    }

    public function updateStatus(User $user, ReferenceProfile $referenceProfile, string $targetStatus): bool
    {
        $involvesArchived = $targetStatus === ReferenceProfileStatus::Archived->value
            || $referenceProfile->status === ReferenceProfileStatus::Archived;

        if (! $involvesArchived) {
            return true;
        }

        return $user->isAdmin();
    }
}
