<?php

namespace Database\Seeders\V1\Auth;

use App\Models\V1\Auth\Role;
use App\Models\V1\Auth\User;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get repository from service container
        $userRepository = app(UserRepositoryInterface::class);

        // Get Super Admin role (must use withSystemRoles to bypass global scope)
        $superAdminRole = Role::withSystemRoles()->where('name', 'Super Admin')->first();
        $ownerRole = Role::withSystemRoles()->where('name', 'Owner')->first();

        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found. Please run RoleSeeder first.');
            return;
        }

        if (!$ownerRole) {
            $this->command->error('Owner role not found. Please run RoleSeeder first.');
            return;
        }

        $this->command->info('Creating users...');
        $this->command->info('');

        // ============================================
        // 1. SUPER ADMIN
        // ============================================
        $superAdmin = $userRepository->findByEmail('admin@owners.com');
        if (!$superAdmin) {
            $superAdmin = $userRepository->create([
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'email' => 'admin@owners.com',
                'password' => 'password',
                'first' => 'Super',
                'last' => 'Admin',
                'company' => 'Owners Management System',
                'type' => 'admin',
                'active' => true,
                'email_verified_at' => now(),
                'timezone' => 'Asia/Riyadh',
                'locale' => 'ar',
            ]);
            $superAdmin->assignRole('Super Admin');
            $this->command->info('✓ Super Admin created: admin@owners.com');
        } else {
            if (!$superAdmin->hasRole('Super Admin')) {
                $superAdmin->assignRole('Super Admin');
            }
            $this->command->info('✓ Super Admin already exists: admin@owners.com');
        }

        // ============================================
        // 2. OWNERS (3)
        // ============================================
        $owners = [
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440010',
                'email' => 'owner1@owners.com',
                'first' => 'Ahmed',
                'last' => 'Al-Rashid',
                'company' => 'Al-Rashid Real Estate',
                'phone' => '+966501234567',
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440011',
                'email' => 'owner2@owners.com',
                'first' => 'Mohammed',
                'last' => 'Al-Noor',
                'company' => 'Al-Noor Property Management',
                'phone' => '+966502345678',
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440012',
                'email' => 'owner3@owners.com',
                'first' => 'Khalid',
                'last' => 'Al-Madinah',
                'company' => 'Al-Madinah Investment Group',
                'phone' => '+966503456789',
            ],
        ];

        $createdOwners = [];
        foreach ($owners as $ownerData) {
            $owner = $userRepository->findByEmail($ownerData['email']);
            if (!$owner) {
                $owner = $userRepository->create([
                    'uuid' => $ownerData['uuid'],
                    'email' => $ownerData['email'],
                    'password' => 'password',
                    'first' => $ownerData['first'],
                    'last' => $ownerData['last'],
                    'company' => $ownerData['company'],
                    'phone' => $ownerData['phone'],
                    'type' => 'owner',
                    'active' => true,
                    'email_verified_at' => now(),
                    'timezone' => 'Asia/Riyadh',
                    'locale' => 'ar',
                ]);
                $owner->assignRole('Owner');
                $this->command->info("✓ Owner created: {$ownerData['email']}");
            } else {
                if (!$owner->hasRole('Owner')) {
                    $owner->assignRole('Owner');
                }
                $this->command->info("✓ Owner already exists: {$ownerData['email']}");
            }
            $createdOwners[] = $owner;
        }

        // ============================================
        // 3. STAFF USERS (Managers, Accountants, etc.)
        // ============================================
        $staffUsers = [
            // Property Managers
            [
                'email' => 'manager1@owners.com',
                'first' => 'Sara',
                'last' => 'Al-Saud',
                'company' => 'Al-Rashid Real Estate',
                'phone' => '+966504567890',
                'type' => 'manager',
            ],
            [
                'email' => 'manager2@owners.com',
                'first' => 'Fatima',
                'last' => 'Al-Zahra',
                'company' => 'Al-Noor Property Management',
                'phone' => '+966505678901',
                'type' => 'manager',
            ],
            [
                'email' => 'manager3@owners.com',
                'first' => 'Noura',
                'last' => 'Al-Fahad',
                'company' => 'Al-Madinah Investment Group',
                'phone' => '+966506789012',
                'type' => 'manager',
            ],
            // Accountants
            [
                'email' => 'accountant1@owners.com',
                'first' => 'Omar',
                'last' => 'Al-Mansour',
                'company' => 'Al-Rashid Real Estate',
                'phone' => '+966507890123',
                'type' => 'accountant',
            ],
            [
                'email' => 'accountant2@owners.com',
                'first' => 'Youssef',
                'last' => 'Al-Hassan',
                'company' => 'Al-Noor Property Management',
                'phone' => '+966508901234',
                'type' => 'accountant',
            ],
            // Maintenance Staff
            [
                'email' => 'maintenance1@owners.com',
                'first' => 'Hassan',
                'last' => 'Al-Ahmad',
                'company' => 'Al-Rashid Real Estate',
                'phone' => '+966509012345',
                'type' => 'maintenance',
            ],
            [
                'email' => 'maintenance2@owners.com',
                'first' => 'Ibrahim',
                'last' => 'Al-Mahmoud',
                'company' => 'Al-Noor Property Management',
                'phone' => '+966500123456',
                'type' => 'maintenance',
            ],
            // Receptionists/Office Staff
            [
                'email' => 'reception1@owners.com',
                'first' => 'Layla',
                'last' => 'Al-Abdullah',
                'company' => 'Al-Rashid Real Estate',
                'phone' => '+966501234567',
                'type' => 'staff',
            ],
            [
                'email' => 'reception2@owners.com',
                'first' => 'Mariam',
                'last' => 'Al-Saleh',
                'company' => 'Al-Noor Property Management',
                'phone' => '+966502345678',
                'type' => 'staff',
            ],
            // Legal/Compliance
            [
                'email' => 'legal1@owners.com',
                'first' => 'Abdullah',
                'last' => 'Al-Khalid',
                'company' => 'Al-Rashid Real Estate',
                'phone' => '+966503456789',
                'type' => 'legal',
            ],
            // Marketing
            [
                'email' => 'marketing1@owners.com',
                'first' => 'Rania',
                'last' => 'Al-Tariq',
                'company' => 'Al-Noor Property Management',
                'phone' => '+966504567890',
                'type' => 'marketing',
            ],
            // IT Support
            [
                'email' => 'it1@owners.com',
                'first' => 'Faisal',
                'last' => 'Al-Mutairi',
                'company' => 'Owners Management System',
                'phone' => '+966505678901',
                'type' => 'it',
            ],
            // Assistant Managers
            [
                'email' => 'assistant1@owners.com',
                'first' => 'Hala',
                'last' => 'Al-Mubarak',
                'company' => 'Al-Madinah Investment Group',
                'phone' => '+966506789012',
                'type' => 'assistant',
            ],
            [
                'email' => 'assistant2@owners.com',
                'first' => 'Nasser',
                'last' => 'Al-Qasimi',
                'company' => 'Al-Rashid Real Estate',
                'phone' => '+966507890123',
                'type' => 'assistant',
            ],
        ];

        $createdStaff = [];
        foreach ($staffUsers as $staffData) {
            $staff = $userRepository->findByEmail($staffData['email']);
            if (!$staff) {
                $staff = $userRepository->create([
                    'uuid' => (string) Str::uuid(),
                    'email' => $staffData['email'],
                    'password' => 'password',
                    'first' => $staffData['first'],
                    'last' => $staffData['last'],
                    'company' => $staffData['company'],
                    'phone' => $staffData['phone'],
                    'type' => $staffData['type'],
                    'active' => true,
                    'email_verified_at' => now(),
                    'timezone' => 'Asia/Riyadh',
                    'locale' => 'ar',
                ]);
                $this->command->info("✓ Staff created: {$staffData['email']} ({$staffData['type']})");
            } else {
                $this->command->info("✓ Staff already exists: {$staffData['email']}");
            }
            $createdStaff[] = $staff;
        }

        $this->command->info('');
        $this->command->info('✅ Users seeded successfully!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('- 1 Super Admin');
        $this->command->info('- 3 Owners');
        $this->command->info('- ' . count($createdStaff) . ' Staff Users');
        $this->command->info('');
        $this->command->info('Default Password for all users: password');
    }
}
