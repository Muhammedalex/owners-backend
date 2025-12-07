<?php

namespace App\Policies\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;

class OwnershipPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.view');
        }

        // Regular users can view if they have permission and access to ownerships
        return $user->can('ownerships.view') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ownership $ownership): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.view');
        }

        // Check permission and ownership access
        return $user->can('ownerships.view') && $user->hasOwnership($ownership->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only Super Admin can create ownerships
        return $user->isSuperAdmin() && $user->can('ownerships.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ownership $ownership): bool
    {
        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.update');
        }

        // Check permission and ownership access
        return $user->can('ownerships.update') && $user->hasOwnership($ownership->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ownership $ownership): bool
    {
        // Only Super Admin can delete ownerships
        return $user->isSuperAdmin() && $user->can('ownerships.delete');
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, Ownership $ownership): bool
    {
        // Super Admin can activate all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.activate');
        }

        // Check permission and ownership access
        return $user->can('ownerships.activate') && $user->hasOwnership($ownership->id);
    }

    /**
     * Determine whether the user can deactivate the model.
     */
    public function deactivate(User $user, Ownership $ownership): bool
    {
        // Super Admin can deactivate all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.deactivate');
        }

        // Check permission and ownership access
        return $user->can('ownerships.deactivate') && $user->hasOwnership($ownership->id);
    }
}
