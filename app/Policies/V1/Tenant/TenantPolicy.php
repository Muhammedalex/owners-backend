<?php

namespace App\Policies\V1\Tenant;

use App\Models\V1\Auth\User;
use App\Models\V1\Tenant\Tenant;

class TenantPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.view');
        }

        // Regular users can view if they have permission and access to ownerships
        return $user->can('tenants.view') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.view');
        }

        // Check permission and ownership access
        return $user->can('tenants.view') && $user->hasOwnership($tenant->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super Admin can create
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.create');
        }

        // Regular users can create if they have permission and access to ownerships
        return $user->can('tenants.create') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.update');
        }

        // Check permission and ownership access
        return $user->can('tenants.update') && $user->hasOwnership($tenant->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.delete');
        }

        // Check permission and ownership access
        return $user->can('tenants.delete') && $user->hasOwnership($tenant->ownership_id);
    }
}

