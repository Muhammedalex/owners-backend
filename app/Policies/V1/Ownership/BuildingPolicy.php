<?php

namespace App\Policies\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Building;

class BuildingPolicy
{
    /**
     * Determine whether the user can view any models.
     * Ownership scope is mandatory - checked via middleware.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('properties.buildings.view');
    }

    /**
     * Determine whether the user can view the model.
     * Ownership scope is mandatory - building must belong to current ownership.
     */
    public function view(User $user, Building $building): bool
    {
        // Ownership scope is mandatory - building must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $building->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.buildings.view');
    }

    /**
     * Determine whether the user can create models.
     * Ownership scope is mandatory - checked via middleware.
     */
    public function create(User $user): bool
    {
        return $user->can('properties.buildings.create');
    }

    /**
     * Determine whether the user can update the model.
     * Ownership scope is mandatory - building must belong to current ownership.
     */
    public function update(User $user, Building $building): bool
    {
        // Ownership scope is mandatory - building must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $building->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.buildings.update');
    }

    /**
     * Determine whether the user can delete the model.
     * Ownership scope is mandatory - building must belong to current ownership.
     */
    public function delete(User $user, Building $building): bool
    {
        // Ownership scope is mandatory - building must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $building->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.buildings.delete');
    }

    /**
     * Determine whether the user can activate the model.
     * Ownership scope is mandatory - building must belong to current ownership.
     */
    public function activate(User $user, Building $building): bool
    {
        // Ownership scope is mandatory - building must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $building->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.buildings.update');
    }

    /**
     * Determine whether the user can deactivate the model.
     * Ownership scope is mandatory - building must belong to current ownership.
     */
    public function deactivate(User $user, Building $building): bool
    {
        // Ownership scope is mandatory - building must belong to current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId || $building->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.buildings.update');
    }
}

