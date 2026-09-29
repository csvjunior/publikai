<?php

namespace App\Policies;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;

/**
 * Autorização de produtos (Sprint 1).
 * Interno: admin e operator visualizam/criam/editam.
 * Qualquer transição para ou a partir de ProductStatus::Archived exige admin;
 * operators alternam apenas entre active e paused.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Autoriza uma mudança de status. Entrar ou sair de `archived` é
     * exclusivo de admin; as demais transições são livres (ambos os papéis).
     */
    public function updateStatus(User $user, Product $product, string $targetStatus): bool
    {
        $involvesArchived = $targetStatus === ProductStatus::Archived->value
            || $product->status === ProductStatus::Archived;

        if (! $involvesArchived) {
            return true;
        }

        return $user->isAdmin();
    }
}
