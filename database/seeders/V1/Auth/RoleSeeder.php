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

        // ============================================
        // 1. SUPER ADMIN ROLE
        // ============================================
        // Super Admin has ALL permissions including:
        // - reports.view (for all report endpoints)
        // - tenants.view (for tenant reports)
        // - contracts.view (for contract reports)
        // - invoices.view (for invoice reports)
        // - payments.view (for payment reports)
        // This allows Super Admin to view system-wide reports without ownership scope
        $superAdmin = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Super Admin'],
            ['guard_name' => 'web']
        );
        $superAdmin->syncPermissions($allPermissions);
        $this->command->info('✓ Super Admin role created with all permissions (including reports.view, tenants.view, contracts.view, invoices.view, payments.view).');

        // ============================================
        // 2. OWNER ROLE
        // ============================================
        // Full access to their ownership(s) - can manage everything
        $owner = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Owner'],
            ['guard_name' => 'web']
        );

        $ownerPermissions = [
            // Own profile & manage user permissions inside ownership
            'auth.users.view.own',
            'auth.users.update.own',
            'auth.users.create',
            'auth.users.activate',
            'auth.users.deactivate',
            'auth.permissions.manage',

            // Ownership management
            'ownerships.view',
            'ownerships.update',
            'ownerships.activate',
            'ownerships.deactivate',
            'ownerships.board.view',
            'ownerships.board.manage',
            'ownerships.users.view',
            'ownerships.users.assign',
            'ownerships.users.remove',
            'ownerships.users.set-default',

            // Property management (full access)
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

            // Tenant management (full access)
            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',
            'tenants.verify',
            'tenants.rating.update',

            // Tenant Invitations (full access)
            'tenants.invitations.view',
            'tenants.invitations.create',
            'tenants.invitations.update',
            'tenants.invitations.delete',
            'tenants.invitations.cancel',
            'tenants.invitations.resend',
            'tenants.invitations.close_without_contact',
            'tenants.invitations.notifications',

            // Contract management (full access)
            'contracts.view',
            'contracts.create',
            'contracts.update',
            'contracts.delete',
            'contracts.approve',
            'contracts.sign',
            'contracts.terminate',
            'contracts.notifications',

            // Invoice Module (full access)
            'invoices.view',
            'invoices.viewAll',
            'invoices.create',
            'invoices.update',
            'invoices.delete',
            'invoices.send',
            'invoices.cancel',
            'invoices.approve',
            'invoices.autoGenerate',
            'invoices.manualCreate',
            'invoices.editSent',
            'invoices.editDraft',
            
            // Payment Module (full access)
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',
            'payments.confirm',
            'payments.markAsPaid',
            'payments.markAsUnpaid',

            // Reports Module
            'reports.view',

            // Settings Module (ownership-scoped, full access)
            'settings.view',
            'settings.create',
            'settings.update',
            'settings.delete',
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

            // Media Module (full access)
            'media.view',
            'media.create',
            'media.update',
            'media.delete',
            'media.upload',
            'media.download',
            'media.reorder',

            // Documents Module (full access)
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
            'documents.upload',
            'documents.download',
            'documents.archive',

            // Maintenance (full access)
            'maintenance.categories.view',
            'maintenance.categories.manage',
            'maintenance.requests.view',
            'maintenance.requests.create',
            'maintenance.requests.update',
            'maintenance.requests.assign',
            'maintenance.technicians.view',
            'maintenance.technicians.manage',

            // Facilities (full access)
            'facilities.view',
            'facilities.create',
            'facilities.update',
            'facilities.delete',
            'facilities.bookings.view',
            'facilities.bookings.create',
            'facilities.bookings.approve',
            'facilities.bookings.cancel',

            // System (limited - no system settings)
            'system.notifications.view',
            'system.documents.view',
            'system.documents.upload',
            'system.documents.delete',
        ];

        $owner->syncPermissions(
            Permission::whereIn('name', $ownerPermissions)->get()
        );
        $this->command->info('✓ Owner role created with full ownership permissions.');

        // ============================================
        // 3. BOARD MEMBER ROLE
        // ============================================
        // Read-only access - can view everything but cannot modify
        $boardMember = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Board Member'],
            ['guard_name' => 'web']
        );

        $boardMemberPermissions = [
            // Own profile
            'auth.users.view.own',
            'auth.users.update.own',

            // Ownership (view only)
            'ownerships.view',
            'ownerships.board.view',
            'ownerships.users.view',

            // Property (view only)
            'properties.portfolios.view',
            'properties.buildings.view',
            'properties.units.view',

            // Tenant (view only)
            'tenants.view',

            // Contract (view only)
            'contracts.view',

            // Invoice (view only)
            'invoices.view',
            
            // Payment (view only)
            'payments.view',

            // Reports (view only)
            'reports.view',

            // Settings (view only)
            'settings.view',
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

            // Media (view only)
            'media.view',
            'media.download',

            // Documents (view only)
            'documents.view',
            'documents.download',

            // Maintenance (view only)
            'maintenance.categories.view',
            'maintenance.requests.view',
            'maintenance.technicians.view',

            // Facilities (view only)
            'facilities.view',
            'facilities.bookings.view',

            // System (view only)
            'system.notifications.view',
            'system.documents.view',
        ];

        $boardMember->syncPermissions(
            Permission::whereIn('name', $boardMemberPermissions)->get()
        );
        $this->command->info('✓ Board Member role created with view-only permissions.');

        // ============================================
        // 4. TENANT ROLE
        // ============================================
        // Can only view their own contracts, invoices, and payments
        $tenant = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Tenant'],
            ['guard_name' => 'web']
        );

        $tenantPermissions = [
            // Own profile
            'auth.users.view.own',
            'auth.users.update.own',

            // Own tenant record (view/update own)
            'tenants.view', // Limited to own record via policy
            'tenants.update', // Limited to own record via policy

            // Own contracts (view only)
            'contracts.view', // Limited to own contracts via policy

            // Own invoices (view only)
            'invoices.view', // Limited to own invoices via policy
            
            // Own payments (view only)
            'payments.view', // Limited to own payments via policy

            // Documents (view own)
            'documents.view', // Limited to own documents via policy
            'documents.download', // Limited to own documents via policy

            // Media (view own)
            'media.view', // Limited to own media via policy
            'media.download', // Limited to own media via policy
        ];

        $tenant->syncPermissions(
            Permission::whereIn('name', $tenantPermissions)->get()
        );
        $this->command->info('✓ Tenant role created with self-access permissions.');

        // ============================================
        // 5. MODERATOR ROLE
        // ============================================
        // Can view, update, and delete but cannot create new records
        $moderator = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Moderator'],
            ['guard_name' => 'web']
        );

        $moderatorPermissions = [
            // Own profile
            'auth.users.view.own',
            'auth.users.update.own',

            // Ownership (view only)
            'ownerships.view',
            'ownerships.board.view',
            'ownerships.users.view',

            // Property (view, update, delete - no create)
            'properties.portfolios.view',
            'properties.portfolios.update',
            'properties.portfolios.delete',
            'properties.buildings.view',
            'properties.buildings.update',
            'properties.buildings.delete',
            'properties.units.view',
            'properties.units.update',
            'properties.units.delete',

            // Tenant (view, update, delete - no create)
            'tenants.view',
            'tenants.update',
            'tenants.delete',
            'tenants.rating.update',

            // Tenant Invitations (view, manage, notifications - no create)
            'tenants.invitations.view',
            'tenants.invitations.cancel',
            'tenants.invitations.resend',
            'tenants.invitations.close_without_contact',
            'tenants.invitations.notifications',

            // Contract (view, update, delete - no create)
            'contracts.view',
            'contracts.update',
            'contracts.delete',
            'contracts.terminate',
            'contracts.notifications',

            // Invoice (view, update, delete - no create)
            'invoices.view',
            'invoices.viewAll',
            'invoices.update',
            'invoices.delete',
            'invoices.send',
            'invoices.cancel',
            'invoices.editSent',
            'invoices.editDraft',
            
            // Payment (view, update, delete - no create)
            'payments.view',
            'payments.update',
            'payments.delete',
            'payments.confirm',

            // Reports (view only)
            'reports.view',

            // Settings (view only)
            'settings.view',
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

            // Media (view, update, delete - no upload)
            'media.view',
            'media.update',
            'media.delete',
            'media.download',
            'media.reorder',

            // Documents (view, update, delete - no upload)
            'documents.view',
            'documents.update',
            'documents.delete',
            'documents.download',
            'documents.archive',

            // Maintenance (view, update, assign - no create)
            'maintenance.categories.view',
            'maintenance.requests.view',
            'maintenance.requests.update',
            'maintenance.requests.assign',
            'maintenance.technicians.view',

            // Facilities (view, update, delete - no create)
            'facilities.view',
            'facilities.update',
            'facilities.delete',
            'facilities.bookings.view',
            'facilities.bookings.approve',
            'facilities.bookings.cancel',

            // System (view only)
            'system.notifications.view',
            'system.documents.view',
        ];

        $moderator->syncPermissions(
            Permission::whereIn('name', $moderatorPermissions)->get()
        );
        $this->command->info('✓ Moderator role created with moderation permissions.');

        // ============================================
        // 6. ACCOUNTANT ROLE
        // ============================================
        // Full financial access - invoices, payments, reports, financial settings
        $accountant = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Accountant'],
            ['guard_name' => 'web']
        );

        $accountantPermissions = [
            // Own profile
            'auth.users.view.own',
            'auth.users.update.own',

            // Ownership (view only)
            'ownerships.view',
            'ownerships.users.view',

            // Property (view only - needed for invoice context)
            'properties.portfolios.view',
            'properties.buildings.view',
            'properties.units.view',

            // Tenant (view only - needed for invoice context)
            'tenants.view',

            // Contract (view only - needed for invoice context)
            'contracts.view',

            // Invoice Module (full access)
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.delete',
            
            // Payment Module (full access)
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',

            // Reports Module (full access)
            'reports.view',

            // Settings (financial only)
            'settings.view',
            'settings.financial.view',
            'settings.financial.update',
            'settings.invoice.view',
            'settings.invoice.update',
            'settings.reporting.view',
            'settings.reporting.update',

            // Documents (view and download - for financial documents)
            'documents.view',
            'documents.download',

            // Media (view and download - for financial media)
            'media.view',
            'media.download',
        ];

        $accountant->syncPermissions(
            Permission::whereIn('name', $accountantPermissions)->get()
        );
        $this->command->info('✓ Accountant role created with financial permissions.');

        // ============================================
        // 7. PROPERTY MANAGER ROLE
        // ============================================
        // Manages properties, tenants, and contracts
        $propertyManager = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Property Manager'],
            ['guard_name' => 'web']
        );

        $propertyManagerPermissions = [
            // Own profile
            'auth.users.view.own',
            'auth.users.update.own',

            // Ownership (view only)
            'ownerships.view',
            'ownerships.users.view',

            // Property management (full access)
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

            // Tenant management (full access)
            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',
            'tenants.verify',
            'tenants.rating.update',

            // Contract management (full access)
            'contracts.view',
            'contracts.create',
            'contracts.update',
            'contracts.delete',
            'contracts.approve',
            'contracts.sign',
            'contracts.terminate',
            'contracts.notifications',

            // Invoice (view and create - for generating invoices)
            'invoices.view',
            'invoices.create',
            'invoices.update',
            
            // Payment (view only)
            'payments.view',

            // Reports (view only)
            'reports.view',

            // Settings (contract and tenant related)
            'settings.view',
            'settings.contract.view',
            'settings.contract.update',
            'settings.tenant.view',
            'settings.tenant.update',

            // Media (full access)
            'media.view',
            'media.create',
            'media.update',
            'media.delete',
            'media.upload',
            'media.download',
            'media.reorder',

            // Documents (full access)
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
            'documents.upload',
            'documents.download',
            'documents.archive',
        ];

        $propertyManager->syncPermissions(
            Permission::whereIn('name', $propertyManagerPermissions)->get()
        );
        $this->command->info('✓ Property Manager role created with property management permissions.');

        // ============================================
        // 8. MAINTENANCE MANAGER ROLE
        // ============================================
        // Manages maintenance requests and technicians
        $maintenanceManager = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Maintenance Manager'],
            ['guard_name' => 'web']
        );

        $maintenanceManagerPermissions = [
            // Own profile
            'auth.users.view.own',
            'auth.users.update.own',

            // Ownership (view only)
            'ownerships.view',

            // Property (view only - needed for maintenance context)
            'properties.portfolios.view',
            'properties.buildings.view',
            'properties.units.view',

            // Tenant (view only - needed for maintenance context)
            'tenants.view',

            // Maintenance (full access)
            'maintenance.categories.view',
            'maintenance.categories.manage',
            'maintenance.requests.view',
            'maintenance.requests.create',
            'maintenance.requests.update',
            'maintenance.requests.assign',
            'maintenance.technicians.view',
            'maintenance.technicians.manage',

            // Reports (view only)
            'reports.view',

            // Settings (maintenance related)
            'settings.view',
            'settings.maintenance.view',
            'settings.maintenance.update',

            // Media (view and upload - for maintenance photos)
            'media.view',
            'media.create',
            'media.upload',
            'media.download',

            // Documents (view and upload - for maintenance documents)
            'documents.view',
            'documents.create',
            'documents.upload',
            'documents.download',
        ];

        $maintenanceManager->syncPermissions(
            Permission::whereIn('name', $maintenanceManagerPermissions)->get()
        );
        $this->command->info('✓ Maintenance Manager role created with maintenance permissions.');

        // ============================================
        // 9. FACILITY MANAGER ROLE
        // ============================================
        // Manages facilities and bookings
        $facilityManager = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Facility Manager'],
            ['guard_name' => 'web']
        );

        $facilityManagerPermissions = [
            // Own profile
            'auth.users.view.own',
            'auth.users.update.own',

            // Ownership (view only)
            'ownerships.view',

            // Facilities (full access)
            'facilities.view',
            'facilities.create',
            'facilities.update',
            'facilities.delete',
            'facilities.bookings.view',
            'facilities.bookings.create',
            'facilities.bookings.approve',
            'facilities.bookings.cancel',

            // Reports (view only)
            'reports.view',

            // Settings (facility related)
            'settings.view',
            'settings.facility.view',
            'settings.facility.update',

            // Media (view and upload - for facility photos)
            'media.view',
            'media.create',
            'media.upload',
            'media.download',
            'media.reorder',

            // Documents (view and upload - for facility documents)
            'documents.view',
            'documents.create',
            'documents.upload',
            'documents.download',
        ];

        $facilityManager->syncPermissions(
            Permission::whereIn('name', $facilityManagerPermissions)->get()
        );
        $this->command->info('✓ Facility Manager role created with facility management permissions.');

        // ============================================
        // 10. COLLECTOR ROLE
        // ============================================
        // Collects payments from assigned tenants
        $collector = Role::withSystemRoles()->firstOrCreate(
            ['name' => 'Collector'],
            ['guard_name' => 'web']
        );

        $collectorPermissions = [
            // Own profile
            // 'auth.users.view.own',
            // 'auth.users.update.own',
            
            // Contracts (view only - limited to assigned tenants)
            'contracts.view',
            'contracts.viewOwn', // View contracts for assigned tenants only
            
            // Invoices (view only - limited to assigned tenants)
            'invoices.view',
            'invoices.viewOwn', // View invoices for assigned tenants only
            
            // Payments (view, create, update - limited to assigned tenants)
            'payments.view',
            'payments.viewAssigned', // View payments for assigned tenants only
            'payments.create', // Create payments
            'payments.update', // Edit/update payments
            
            // Tenants (view only - limited to assigned tenants)
            'tenants.view',
            'tenants.viewAssigned', // View assigned tenants only
            
            // Units (view only - to see units related to contracts)
            'properties.units.view', // View units
        ];

        $collector->syncPermissions(
            Permission::whereIn('name', $collectorPermissions)->get()
        );
        $this->command->info('✓ Collector role created with collection permissions.');

        // ============================================
        // Update Owner, Moderator, Accountant roles with collector management permissions
        // ============================================
        
        // Owner role - add collector management
        $ownerCollectorPermissions = [
            'invoices.collectors.view',
            'invoices.collectors.manage',
            'invoices.collectors.assign',
        ];
        $owner->givePermissionTo(
            Permission::whereIn('name', $ownerCollectorPermissions)->get()
        );
        
        // Moderator role - add collector management
        $moderatorCollectorPermissions = [
            'invoices.collectors.view',
            'invoices.collectors.manage',
            'invoices.collectors.assign',
        ];
        $moderator->givePermissionTo(
            Permission::whereIn('name', $moderatorCollectorPermissions)->get()
        );
        
        // Accountant role - add collector management
        $accountantCollectorPermissions = [
            'invoices.collectors.view',
            'invoices.collectors.manage',
            'invoices.collectors.assign',
        ];
        $accountant->givePermissionTo(
            Permission::whereIn('name', $accountantCollectorPermissions)->get()
        );

        $this->command->info('');
        $this->command->info('✅ All roles seeded successfully!');
    }
}
