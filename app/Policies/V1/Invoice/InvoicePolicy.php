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

        // Collectors can view invoices for assigned tenants only
        if ($user->isCollector()) {
            $ownershipId = request()->input('current_ownership_id');
            if (!$ownershipId) {
                return false;
            }

            // If invoice is standalone (no contract), collectors cannot see it
            if (!$invoice->contract_id) {
                return false;
            }

            // Check if collector can see the tenant
            $collectorService = app(\App\Services\V1\Invoice\CollectorService::class);
            $tenantId = $invoice->contract->tenant_id;
            return $collectorService->canSeeTenant($user, $tenantId, $ownershipId);
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

    /**
     * Determine whether the user can send the invoice.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        // Super Admin can send all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.send');
        }

        // Check permission and ownership access
        return $user->can('invoices.send') && $user->hasOwnership($invoice->ownership_id);
    }

    /**
     * Determine whether the user can cancel the invoice.
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        // Super Admin can cancel all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.cancel');
        }

        // Check permission and ownership access
        return $user->can('invoices.cancel') && $user->hasOwnership($invoice->ownership_id);
    }

    /**
     * Determine whether the user can approve the invoice.
     */
    public function approve(User $user, Invoice $invoice): bool
    {
        // Super Admin can approve all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.approve');
        }

        // Check permission and ownership access
        return $user->can('invoices.approve') && $user->hasOwnership($invoice->ownership_id);
    }

    /**
     * Determine whether the user can edit sent invoices.
     */
    public function editSent(User $user, Invoice $invoice): bool
    {
        // Super Admin can edit all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.editSent');
        }

        // Check permission and ownership access
        if (!$user->can('invoices.editSent') || !$user->hasOwnership($invoice->ownership_id)) {
            return false;
        }

        // Use InvoiceEditRulesService to check if can edit (handles all status checks and settings)
        $editRulesService = app(\App\Services\V1\Invoice\InvoiceEditRulesService::class);
        return $editRulesService->canEdit($invoice, $user);
    }

    /**
     * Determine whether the user can edit draft invoices.
     */
    public function editDraft(User $user, Invoice $invoice): bool
    {
        // Super Admin can edit all
        if ($user->isSuperAdmin()) {
            return $user->can('invoices.editDraft');
        }

        // Check permission and ownership access
        if (!$user->can('invoices.editDraft') || !$user->hasOwnership($invoice->ownership_id)) {
            return false;
        }

        // Check if invoice is draft
        return $invoice->isDraft();
    }

    /**
     * Determine whether the user can view all invoices (not just assigned).
     */
    public function viewAll(User $user): bool
    {
        return $user->can('invoices.viewAll');
    }
}

