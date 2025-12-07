<?php

namespace App\Policies\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\OwnershipBoardMember;

class OwnershipBoardMemberPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.board.view');
        }

        // Regular users can view if they have permission
        return $user->can('ownerships.board.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OwnershipBoardMember $boardMember): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.board.view');
        }

        // Check permission and ownership access
        return $user->can('ownerships.board.view') 
            && $user->hasOwnership($boardMember->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super Admin can create for all ownerships
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.board.manage');
        }

        // Regular users need permission and ownership access
        // Note: ownership_id will be checked in the request/controller
        return $user->can('ownerships.board.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OwnershipBoardMember $boardMember): bool
    {
        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.board.manage');
        }

        // Check permission and ownership access
        return $user->can('ownerships.board.manage') 
            && $user->hasOwnership($boardMember->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OwnershipBoardMember $boardMember): bool
    {
        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            return $user->can('ownerships.board.manage');
        }

        // Check permission and ownership access
        return $user->can('ownerships.board.manage') 
            && $user->hasOwnership($boardMember->ownership_id);
    }
}
