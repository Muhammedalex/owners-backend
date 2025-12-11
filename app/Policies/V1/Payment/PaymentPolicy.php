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

        // Check permission and ownership access
        return $user->can('payments.view') && $user->hasOwnership($payment->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
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
}

