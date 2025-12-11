<?php

namespace App\Policies\V1\Setting;

use App\Models\V1\Auth\User;
use App\Models\V1\Setting\SystemSetting;

class SystemSettingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return $user->can('settings.view') || $user->can('settings.system.view');
        }

        // Regular users can view if they have permission and access to ownerships
        return ($user->can('settings.view') || $user->hasAnyPermission([
            'settings.financial.view',
            'settings.contract.view',
            'settings.invoice.view',
            'settings.tenant.view',
            'settings.notification.view',
            'settings.document.view',
            'settings.media.view',
            'settings.reporting.view',
            'settings.localization.view',
            'settings.security.view',
        ])) && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SystemSetting $setting): bool
    {
        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            if ($setting->isSystemWide()) {
                return $user->can('settings.system.view');
            }
            return $user->can("settings.{$setting->group}.view") || $user->can('settings.view');
        }

        // System-wide settings - Super Admin only
        if ($setting->isSystemWide()) {
            return false;
        }

        // Check group permission
        $permission = "settings.{$setting->group}.view";
        if (!$user->can($permission)) {
            return false;
        }

        // Check ownership access
        return $user->hasOwnership($setting->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super Admin can create system-wide settings
        if ($user->isSuperAdmin()) {
            return $user->can('settings.create') || $user->can('settings.system.update');
        }

        // Regular users can create ownership-specific settings if they have permission
        return ($user->can('settings.create') || $user->hasAnyPermission([
            'settings.financial.update',
            'settings.contract.update',
            'settings.invoice.update',
            'settings.tenant.update',
            'settings.notification.update',
            'settings.document.update',
            'settings.media.update',
            'settings.reporting.update',
            'settings.localization.update',
            'settings.security.update',
        ])) && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SystemSetting $setting): bool
    {
        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            if ($setting->isSystemWide()) {
                return $user->can('settings.system.update');
            }
            return $user->can("settings.{$setting->group}.update") || $user->can('settings.update');
        }

        // System-wide settings - Super Admin only
        if ($setting->isSystemWide()) {
            return false;
        }

        // Check group permission
        $permission = "settings.{$setting->group}.update";
        if (!$user->can($permission)) {
            return false;
        }

        // Check ownership access
        return $user->hasOwnership($setting->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SystemSetting $setting): bool
    {
        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            if ($setting->isSystemWide()) {
                return $user->can('settings.system.update');
            }
            return $user->can("settings.{$setting->group}.update") || $user->can('settings.delete');
        }

        // System-wide settings - Super Admin only
        if ($setting->isSystemWide()) {
            return false;
        }

        // Check group permission
        $permission = "settings.{$setting->group}.update";
        if (!$user->can($permission)) {
            return false;
        }

        // Check ownership access
        return $user->hasOwnership($setting->ownership_id);
    }
}

