<?php

namespace App\Policies\V1\Tenant;

use App\Models\V1\Auth\User;
use App\Models\V1\Tenant\TenantInvitation;

class TenantInvitationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.invitations.view');
        }

        // Regular users can view if they have permission and access to ownerships
        return $user->can('tenants.invitations.view') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TenantInvitation $invitation): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.invitations.view');
        }

        // Check permission and ownership access
        return $user->can('tenants.invitations.view') && $user->hasOwnership($invitation->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super Admin can create
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.invitations.create');
        }

        // Regular users can create if they have permission and access to ownerships
        return $user->can('tenants.invitations.create') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TenantInvitation $invitation): bool
    {
        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.invitations.update');
        }

        // Check permission and ownership access
        return $user->can('tenants.invitations.update') && $user->hasOwnership($invitation->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TenantInvitation $invitation): bool
    {
        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.invitations.delete');
        }

        // Check permission and ownership access
        return $user->can('tenants.invitations.delete') && $user->hasOwnership($invitation->ownership_id);
    }

    /**
     * Determine whether the user can cancel the invitation.
     */
    public function cancel(User $user, TenantInvitation $invitation): bool
    {
        // Super Admin can cancel all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.invitations.cancel');
        }

        // Check permission and ownership access
        return $user->can('tenants.invitations.cancel') && $user->hasOwnership($invitation->ownership_id);
    }

    /**
     * Determine whether the user can resend the invitation.
     */
    public function resend(User $user, TenantInvitation $invitation): bool
    {
        // Super Admin can resend all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.invitations.resend');
        }

        // Check permission and ownership access
        return $user->can('tenants.invitations.resend') && $user->hasOwnership($invitation->ownership_id);
    }

    /**
     * Determine whether the user can close/cancel invitations without email/phone.
     * This requires special permission as these invitations can only be closed manually.
     */
    public function closeWithoutContact(User $user, TenantInvitation $invitation): bool
    {
        // Only allow if invitation has no email and no phone
        if ($invitation->email || $invitation->phone) {
            return false;
        }

        // Super Admin can close all
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.invitations.close_without_contact');
        }

        // Check permission and ownership access
        return $user->can('tenants.invitations.close_without_contact') && $user->hasOwnership($invitation->ownership_id);
    }

    /**
     * Determine whether the user should receive notifications about invitations.
     */
    public function receiveNotifications(User $user, int $ownershipId): bool
    {
        // Super Admin can receive notifications if they have permission
        if ($user->isSuperAdmin()) {
            return $user->can('tenants.invitations.notifications');
        }

        // Check permission and ownership access
        return $user->can('tenants.invitations.notifications') 
            && $user->hasOwnership($ownershipId);
    }
}

