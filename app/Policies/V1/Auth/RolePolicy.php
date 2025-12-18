<?php

namespace App\Policies\V1\Auth;

use App\Models\V1\Auth\Role;
use App\Models\V1\Auth\User;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all roles
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('auth.roles.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        // Super Admin can view all roles
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('auth.roles.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super Admin can create roles
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('auth.roles.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        // Super Admin can update all roles
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('auth.roles.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        // Super Admin can delete all roles
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('auth.roles.delete');
    }

    /**
     * Determine whether the user can assign permissions to the role.
     */
    public function assignPermissions(User $user, Role $role): bool
    {
        // Super Admin can assign permissions to all roles
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('auth.roles.update');
    }
}

