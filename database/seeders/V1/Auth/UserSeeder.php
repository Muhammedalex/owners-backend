<?php

namespace Database\Seeders\V1\Auth;

use App\Models\V1\Auth\Role;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Seeder;

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

        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found. Please run RoleSeeder first.');
            return;
        }

        $this->command->info('Creating users...');
        $this->command->info('');

        // ============================================
        // 1. SUPER ADMIN ONLY
        // Note: Other users (owner, tenants) are created by BumahrizCenterSeeder
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

        $this->command->info('');
        $this->command->info('✅ Users seeded successfully!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('- 1 Super Admin');
        $this->command->info('');
        $this->command->info('Note: Other users (owner, tenants) are created by BumahrizCenterSeeder');
        $this->command->info('Default Password for all users: password');
    }
}
