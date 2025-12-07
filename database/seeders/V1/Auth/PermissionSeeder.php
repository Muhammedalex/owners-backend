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

            // Billing & Payment Module (for future)
            'billing.invoices.view',
            'billing.invoices.create',
            'billing.invoices.update',
            'billing.invoices.delete',
            'billing.invoices.generate',
            'billing.payments.view',
            'billing.payments.create',
            'billing.payments.confirm',
            'billing.reports.view',

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
