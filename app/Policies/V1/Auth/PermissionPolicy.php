<?php

namespace App\Policies\V1\Auth;

use App\Models\V1\Auth\Permission;
use App\Models\V1\Auth\User;

/**
 * Permission Policy - Read Only
 * 
 * Permissions are hard-coded in seeders and cannot be created/updated/deleted via API.
 * Only viewing operations are allowed.
 */
class PermissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->can('auth.permissions.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Permission $permission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return $user->can('auth.permissions.view');
    }

    // Note: create, update, delete methods removed
    // Permissions are hard-coded in seeders and cannot be modified via API
}

