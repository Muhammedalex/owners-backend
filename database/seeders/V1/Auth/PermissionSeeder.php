<?php

namespace Database\Seeders\V1\Auth;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Auth Module - User Management
            'auth.users.view',
            'auth.users.create',
            'auth.users.update',
            'auth.users.delete',
            'auth.users.activate',
            'auth.users.deactivate',
            'auth.users.view.own',
            'auth.users.update.own',

            // Auth Module - Role Management (Super Admin Only)
            'auth.roles.view',
            'auth.roles.create',
            'auth.roles.update',
            'auth.roles.delete',
            'auth.roles.assign',

            // Auth Module - Permission Management (Super Admin Only)
            'auth.permissions.view',
            'auth.permissions.assign',

            // Ownership Module
            'ownerships.view',              // View ownerships list/details
            'ownerships.create',            // Create new ownership
            'ownerships.update',            // Update ownership details
            'ownerships.delete',            // Delete ownership
            'ownerships.activate',          // Activate ownership
            'ownerships.deactivate',        // Deactivate ownership
            'ownerships.switch',            // Switch active ownership (set cookie)
            'ownerships.board.view',        // View board members
            'ownerships.board.manage',      // Add/remove/update board members
            'ownerships.users.view',        // View ownership users
            'ownerships.users.assign',      // Assign users to ownership
            'ownerships.users.remove',      // Remove users from ownership
            'ownerships.users.set-default', // Set default ownership for user

            // Property Management Module (for future)
            'properties.portfolios.view',
            'properties.portfolios.create',
            'properties.portfolios.update',
            'properties.portfolios.delete',
            'properties.buildings.view',
            'properties.buildings.create',
            'properties.buildings.update',
            'properties.buildings.delete',
            'properties.units.view',
            'properties.units.create',
            'properties.units.update',
            'properties.units.delete',

            // Tenant Management Module (for future)
            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',
            'tenants.verify',
            'tenants.rating.update',

            // Contract Management Module (for future)
            'contracts.view',
            'contracts.create',
            'contracts.update',
            'contracts.delete',
            'contracts.approve',
            'contracts.sign',
            'contracts.terminate',

            // Invoice Module
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.delete',
            
            // Payment Module
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',

            // Reports Module
            'reports.view',

            // Settings Module
            'settings.view',
            'settings.create',
            'settings.update',
            'settings.delete',
            // Settings by group
            'settings.financial.view',
            'settings.financial.update',
            'settings.contract.view',
            'settings.contract.update',
            'settings.invoice.view',
            'settings.invoice.update',
            'settings.tenant.view',
            'settings.tenant.update',
            'settings.notification.view',
            'settings.notification.update',
            'settings.maintenance.view',
            'settings.maintenance.update',
            'settings.facility.view',
            'settings.facility.update',
            'settings.document.view',
            'settings.document.update',
            'settings.media.view',
            'settings.media.update',
            'settings.reporting.view',
            'settings.reporting.update',
            'settings.localization.view',
            'settings.localization.update',
            'settings.security.view',
            'settings.security.update',
            'settings.system.view', // Super Admin only
            'settings.system.update', // Super Admin only

            // Media Module
            'media.view',
            'media.create',
            'media.update',
            'media.delete',
            'media.upload',
            'media.download',
            'media.reorder',

            // Documents Module
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
            'documents.upload',
            'documents.download',
            'documents.archive',

            // Maintenance Module (for future)
            'maintenance.categories.view',
            'maintenance.categories.manage',
            'maintenance.requests.view',
            'maintenance.requests.create',
            'maintenance.requests.update',
            'maintenance.requests.assign',
            'maintenance.technicians.view',
            'maintenance.technicians.manage',

            // Facility Management Module (for future)
            'facilities.view',
            'facilities.create',
            'facilities.update',
            'facilities.delete',
            'facilities.bookings.view',
            'facilities.bookings.create',
            'facilities.bookings.approve',
            'facilities.bookings.cancel',

            // System Module (for future)
            'system.settings.view',
            'system.settings.update',
            'system.notifications.view',
            'system.notifications.send',
            'system.audit.view',
            'system.documents.view',
            'system.documents.upload',
            'system.documents.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('Permissions seeded successfully!');
    }
}
