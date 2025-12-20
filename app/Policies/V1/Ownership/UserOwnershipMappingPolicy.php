<?php

namespace App\Policies\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Models\V1\Ownership\UserOwnershipMapping;

class UserOwnershipMappingPolicy
{
    /**
     * Determine whether the user can view any user ownership mappings.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.users.view');
        }

        // Regular users can view if they have permission
        return $user->can('ownerships.users.view');
    }

    /**
     * Determine whether the user can view user ownership mappings for a specific user.
     */
    public function viewUserOwnerships(User $user, User $targetUser): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.users.view');
        }

        // Users can view their own ownerships, or if they have permission and access to the ownerships
        if ($user->id === $targetUser->id) {
            return true; // Users can always view their own ownerships
        }

        // Check permission
        return $user->can('ownerships.users.view');
    }

    /**
     * Determine whether the user can view users for a specific ownership.
     */
    public function viewOwnershipUsers(User $user, Ownership $ownership): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.users.view');
        }

        // Check permission and ownership access
        return $user->can('ownerships.users.view') && $user->hasOwnership($ownership->id);
    }

    /**
     * Determine whether the user can assign users to ownership.
     */
    public function assign(User $user, ?Ownership $ownership = null): bool
    {
        // Super Admin can assign to any ownership
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.users.assign');
        }

        // For non-Super Admin, ownership must be provided and user must have access
        if (!$ownership) {
            return false;
        }

        // Check permission and ownership access
        return $user->can('ownerships.users.assign') && $user->hasOwnership($ownership->id);
    }

    /**
     * Determine whether the user can remove users from ownership.
     */
    public function remove(User $user, Ownership $ownership): bool
    {
        // Super Admin can remove from any ownership
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.users.remove');
        }

        // Check permission and ownership access
        return $user->can('ownerships.users.remove') && $user->hasOwnership($ownership->id);
    }

    /**
     * Determine whether the user can set default ownership for a user.
     */
    public function setDefault(User $user, User $targetUser, Ownership $ownership): bool
    {
        // Super Admin can set default for any user
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.users.set-default');
        }

        // Users can set their own default, or if they have permission and access to the ownership
        if ($user->id === $targetUser->id) {
            // Users can set their own default if they have access to the ownership
            return $user->hasOwnership($ownership->id);
        }

        // Check permission and ownership access
        return $user->can('ownerships.users.set-default') && $user->hasOwnership($ownership->id);
    }
}

