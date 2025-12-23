<?php

namespace App\Policies\V1\Payment;

use App\Models\V1\Auth\User;
use App\Models\V1\Payment\Payment;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('payments.view');
        }

        // Collectors can view if they have payments.viewAssigned permission
        if ($user->isCollector()) {
            return ($user->can('payments.viewAssigned') || $user->can('payments.view')) && $user->ownershipMappings()->exists();
        }

        // Regular users can view if they have permission and access to ownerships
        return $user->can('payments.view') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('payments.view');
        }

        // Collectors can view payments for assigned tenants only
        if ($user->isCollector()) {
            $ownershipId = request()->input('current_ownership_id');
            if (!$ownershipId) {
                return false;
            }

            // Check if payment belongs to an invoice with a contract
            if ($payment->invoice && $payment->invoice->contract) {
                $tenantId = $payment->invoice->contract->tenant_id;
                $collectorService = app(\App\Services\V1\Invoice\CollectorService::class);
                return $collectorService->canSeeTenant($user, $tenantId, $ownershipId);
            }

            // If no contract, collectors cannot see standalone invoice payments
            return false;
        }

        // Check permission and ownership access
        return $user->can('payments.view') && $user->hasOwnership($payment->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Collectors can create payments if they have payments.create permission
        if ($user->isCollector()) {
            return $user->can('payments.create') && $user->ownershipMappings()->exists();
        }

        // Super Admin can create
        if ($user->isSuperAdmin()) {
            return $user->can('payments.create');
        }

        // Regular users can create if they have permission and access to ownerships
        return $user->can('payments.create') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payment $payment): bool
    {
        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return $user->can('payments.update');
        }

        // Collectors can update payments for assigned tenants only
        if ($user->isCollector()) {
            if (!$user->can('payments.update')) {
                return false;
            }

            $ownershipId = request()->input('current_ownership_id');
            if (!$ownershipId) {
                return false;
            }

            // Check if payment belongs to an invoice with a contract
            if ($payment->invoice && $payment->invoice->contract) {
                $tenantId = $payment->invoice->contract->tenant_id;
                $collectorService = app(\App\Services\V1\Invoice\CollectorService::class);
                return $collectorService->canSeeTenant($user, $tenantId, $ownershipId);
            }

            // If no contract, collectors cannot update standalone invoice payments
            return false;
        }

        // Check permission and ownership access
        return $user->can('payments.update') && $user->hasOwnership($payment->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): bool
    {
        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            return $user->can('payments.delete');
        }

        // Check permission and ownership access
        return $user->can('payments.delete') && $user->hasOwnership($payment->ownership_id);
    }

    /**
     * Determine whether the user can confirm the payment.
     */
    public function confirm(User $user, Payment $payment): bool
    {
        // Super Admin can confirm all
        if ($user->isSuperAdmin()) {
            return $user->can('payments.confirm');
        }

        // Check permission and ownership access
        return $user->can('payments.confirm') && $user->hasOwnership($payment->ownership_id);
    }

    /**
     * Determine whether the user can view assigned payments (collectors).
     */
    public function viewAssigned(User $user): bool
    {
        return $user->can('payments.viewAssigned');
    }
}

