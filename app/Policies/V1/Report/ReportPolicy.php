<?php

namespace App\Policies\V1\Report;

use App\Models\V1\Auth\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view reports.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all reports (system-wide access)
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Regular users can view reports if they have permission and access to ownerships
        return $user->can('reports.view') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view tenant reports.
     */
    public function viewTenants(User $user): bool
    {
        // Super Admin can view all tenant reports (system-wide access)
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ($user->can('reports.view') || $user->can('tenants.view')) && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view contract reports.
     */
    public function viewContracts(User $user): bool
    {
        // Super Admin can view all contract reports (system-wide access)
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ($user->can('reports.view') || $user->can('contracts.view')) && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view invoice reports.
     */
    public function viewInvoices(User $user): bool
    {
        // Super Admin can view all invoice reports (system-wide access)
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ($user->can('reports.view') || $user->can('invoices.view')) && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view payment reports.
     */
    public function viewPayments(User $user): bool
    {
        // Super Admin can view all payment reports (system-wide access)
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ($user->can('reports.view') || $user->can('payments.view')) && $user->ownershipMappings()->exists();
    }
}

