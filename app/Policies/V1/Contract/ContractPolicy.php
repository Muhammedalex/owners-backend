<?php

namespace App\Policies\V1\Contract;

use App\Models\V1\Auth\User;
use App\Models\V1\Contract\Contract;

class ContractPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('contracts.view');
        }

        // Regular users can view if they have permission and access to ownerships
        return $user->can('contracts.view') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contract $contract): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('contracts.view');
        }

        // Check permission and ownership access
        return $user->can('contracts.view') && $user->hasOwnership($contract->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super Admin can create
        if ($user->isSuperAdmin()) {
            return $user->can('contracts.create');
        }

        // Regular users can create if they have permission and access to ownerships
        return $user->can('contracts.create') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contract $contract): bool
    {
        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return $user->can('contracts.update');
        }

        // Check permission and ownership access
        return $user->can('contracts.update') && $user->hasOwnership($contract->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contract $contract): bool
    {
        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            return $user->can('contracts.delete');
        }

        // Check permission and ownership access
        return $user->can('contracts.delete') && $user->hasOwnership($contract->ownership_id);
    }

    /**
     * Determine whether the user can approve the contract.
     */
    public function approve(User $user, Contract $contract): bool
    {
        // Super Admin can approve all
        if ($user->isSuperAdmin()) {
            return $user->can('contracts.approve');
        }

        // Check permission and ownership access
        return $user->can('contracts.approve') && $user->hasOwnership($contract->ownership_id);
    }

    /**
     * Determine whether the user can cancel the contract.
     */
    public function cancel(User $user, Contract $contract): bool
    {
        // Super Admin can cancel all
        if ($user->isSuperAdmin()) {
            return $user->can('contracts.terminate');
        }

        // Check permission and ownership access
        return $user->can('contracts.terminate') && $user->hasOwnership($contract->ownership_id);
    }

    /**
     * Determine whether the user can terminate the contract.
     */
    public function terminate(User $user, Contract $contract): bool
    {
        // Super Admin can terminate all
        if ($user->isSuperAdmin()) {
            return $user->can('contracts.terminate');
        }

        // Check permission and ownership access
        return $user->can('contracts.terminate') && $user->hasOwnership($contract->ownership_id);
    }
}

