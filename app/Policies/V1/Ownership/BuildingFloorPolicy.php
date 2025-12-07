<?php

namespace App\Policies\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\BuildingFloor;

class BuildingFloorPolicy
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
     * Ownership scope is mandatory - floor must belong to building in current ownership.
     */
    public function view(User $user, BuildingFloor $buildingFloor): bool
    {
        // Ownership scope is mandatory - floor must belong to building in current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId) {
            return false;
        }

        // Check if building belongs to ownership
        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $currentOwnershipId) {
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
     * Ownership scope is mandatory - floor must belong to building in current ownership.
     */
    public function update(User $user, BuildingFloor $buildingFloor): bool
    {
        // Ownership scope is mandatory - floor must belong to building in current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId) {
            return false;
        }

        // Check if building belongs to ownership
        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.buildings.update');
    }

    /**
     * Determine whether the user can delete the model.
     * Ownership scope is mandatory - floor must belong to building in current ownership.
     */
    public function delete(User $user, BuildingFloor $buildingFloor): bool
    {
        // Ownership scope is mandatory - floor must belong to building in current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId) {
            return false;
        }

        // Check if building belongs to ownership
        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.buildings.delete');
    }

    /**
     * Determine whether the user can activate the model.
     * Ownership scope is mandatory - floor must belong to building in current ownership.
     */
    public function activate(User $user, BuildingFloor $buildingFloor): bool
    {
        // Ownership scope is mandatory - floor must belong to building in current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId) {
            return false;
        }

        // Check if building belongs to ownership
        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.buildings.update');
    }

    /**
     * Determine whether the user can deactivate the model.
     * Ownership scope is mandatory - floor must belong to building in current ownership.
     */
    public function deactivate(User $user, BuildingFloor $buildingFloor): bool
    {
        // Ownership scope is mandatory - floor must belong to building in current ownership
        $currentOwnershipId = request()->input('current_ownership_id');
        if (!$currentOwnershipId) {
            return false;
        }

        // Check if building belongs to ownership
        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $currentOwnershipId) {
            return false;
        }

        return $user->can('properties.buildings.update');
    }
}

