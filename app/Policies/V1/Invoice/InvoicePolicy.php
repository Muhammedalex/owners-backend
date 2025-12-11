<?php

namespace App\Policies\V1\Invoice;

use App\Models\V1\Auth\User;
use App\Models\V1\Invoice\Invoice;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.view');
        }

        // Regular users can view if they have permission and access to ownerships
        return $user->can('invoices.view') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.view');
        }

        // Check permission and ownership access
        return $user->can('invoices.view') && $user->hasOwnership($invoice->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super Admin can create
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.create');
        }

        // Regular users can create if they have permission and access to ownerships
        return $user->can('invoices.create') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.update');
        }

        // Check permission and ownership access
        return $user->can('invoices.update') && $user->hasOwnership($invoice->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.delete');
        }

        // Check permission and ownership access
        return $user->can('invoices.delete') && $user->hasOwnership($invoice->ownership_id);
    }
}

