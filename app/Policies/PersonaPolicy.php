<?php

namespace App\Policies;

use App\Enums\PersonaStatus;
use App\Models\Persona;
use App\Models\User;

/**
 * Autorização de personas (Sprint 3).
 * Mesma regra consolidada: entrar/sair de `archived` só admin.
 */
class PersonaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Persona $persona): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Persona $persona): bool
    {
        return true;
    }

    public function updateStatus(User $user, Persona $persona, string $targetStatus): bool
    {
        $involvesArchived = $targetStatus === PersonaStatus::Archived->value
            || $persona->status === PersonaStatus::Archived;

        if (! $involvesArchived) {
            return true;
        }

        return $user->isAdmin();
    }
}
