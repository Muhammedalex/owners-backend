<?php

namespace App\Policies\V1\Invoice;

use App\Models\V1\Invoice\CollectorTenantAssignment;
use App\Models\V1\Auth\User;

class CollectorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.collectors.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CollectorTenantAssignment $assignment): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.collectors.view');
        }

        // Check permission and ownership access
        return $user->can('invoices.collectors.view') && $user->hasOwnership($assignment->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('invoices.collectors.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CollectorTenantAssignment $assignment): bool
    {
        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.collectors.manage');
        }

        // Check permission and ownership access
        return $user->can('invoices.collectors.manage') && $user->hasOwnership($assignment->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CollectorTenantAssignment $assignment): bool
    {
        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.collectors.manage');
        }

        // Check permission and ownership access
        return $user->can('invoices.collectors.manage') && $user->hasOwnership($assignment->ownership_id);
    }

    /**
     * Determine whether the user can assign tenants to collectors.
     */
    public function assign(User $user): bool
    {
        return $user->can('invoices.collectors.assign');
    }
}
