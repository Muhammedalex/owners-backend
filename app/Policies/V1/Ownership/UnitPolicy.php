<?php

namespace App\Policies\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Unit;

class UnitPolicy
{
    /**
     * Determine whether the user can view any models.
     * Ownership scope is mandatory - checked via middleware.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('properties.units.view');
    }

    /**
     * Determine whether the user can view the model.
     * Ownership scope is mandatory - unit must belong to current ownership.
     */
    public function view(User $user, Unit $unit): bool
    {
        // Ownership scope is mandatory - unit must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $unit->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.units.view');
    }

    /**
     * Determine whether the user can create models.
     * Ownership scope is mandatory - checked via middleware.
     */
    public function create(User $user): bool
    {
        return $user->can('properties.units.create');
    }

    /**
     * Determine whether the user can update the model.
     * Ownership scope is mandatory - unit must belong to current ownership.
     */
    public function update(User $user, Unit $unit): bool
    {
        // Ownership scope is mandatory - unit must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $unit->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.units.update');
    }

    /**
     * Determine whether the user can delete the model.
     * Ownership scope is mandatory - unit must belong to current ownership.
     */
    public function delete(User $user, Unit $unit): bool
    {
        // Ownership scope is mandatory - unit must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $unit->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.units.delete');
    }

    /**
     * Determine whether the user can activate the model.
     * Ownership scope is mandatory - unit must belong to current ownership.
     */
    public function activate(User $user, Unit $unit): bool
    {
        // Ownership scope is mandatory - unit must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $unit->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.units.update');
    }

    /**
     * Determine whether the user can deactivate the model.
     * Ownership scope is mandatory - unit must belong to current ownership.
     */
    public function deactivate(User $user, Unit $unit): bool
    {
        // Ownership scope is mandatory - unit must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $unit->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.units.update');
    }
}

