<?php

namespace Database\Seeders\V1\Auth;

use App\Models\V1\Auth\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all permissions
        $allPermissions = Permission::all();

        // Create Super Admin Role (must use withSystemRoles to bypass global scope)
        $superAdmin = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Super Admin'],
            ['guard_name' => 'web']
        );

        // Assign ALL permissions to Super Admin
        $superAdmin->syncPermissions($allPermissions);

        $this->command->info('Super Admin role created with all permissions.');

        // Create Owner Role (must use withSystemRoles to bypass global scope)
        $owner = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Owner'],
            ['guard_name' => 'web']
        );

        // Owner permissions - limited to their ownership scope
        $ownerPermissions = [
            // Own profile
            'auth.users.view.own',
            'auth.users.update.own',

            // Ownership management
            'ownerships.view',
            'ownerships.create',
            'ownerships.update',
            'ownerships.delete',
            'ownerships.activate',
            'ownerships.deactivate',
            'ownerships.board.view',
            'ownerships.board.manage',
            'ownerships.users.assign',

            // Property management
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

            // Tenant management
            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',
            'tenants.verify',
            'tenants.rating.update',

            // Contract management
            'contracts.view',
            'contracts.create',
            'contracts.update',
            'contracts.delete',
            'contracts.approve',
            'contracts.sign',
            'contracts.terminate',

            // Billing & Payment
            'billing.invoices.view',
            'billing.invoices.create',
            'billing.invoices.update',
            'billing.invoices.delete',
            'billing.invoices.generate',
            'billing.payments.view',
            'billing.payments.create',
            'billing.payments.confirm',
            'billing.reports.view',

            // Maintenance
            'maintenance.categories.view',
            'maintenance.categories.manage',
            'maintenance.requests.view',
            'maintenance.requests.create',
            'maintenance.requests.update',
            'maintenance.requests.assign',
            'maintenance.technicians.view',
            'maintenance.technicians.manage',

            // Facilities
            'facilities.view',
            'facilities.create',
            'facilities.update',
            'facilities.delete',
            'facilities.bookings.view',
            'facilities.bookings.create',
            'facilities.bookings.approve',
            'facilities.bookings.cancel',

            // System (limited)
            'system.notifications.view',
            'system.documents.view',
            'system.documents.upload',
            'system.documents.delete',
        ];

        // Assign permissions to Owner role
        $owner->syncPermissions(
            Permission::whereIn('name', $ownerPermissions)->get()
        );

        $this->command->info('Owner role created with appropriate permissions.');

        $this->command->info('Roles seeded successfully!');
    }
}
