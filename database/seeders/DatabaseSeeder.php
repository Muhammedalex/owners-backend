<?php

namespace Database\Seeders;

use App\Models\V1\Auth\Role;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use Database\Seeders\V1\Auth\PermissionSeeder;
use Database\Seeders\V1\Auth\RoleSeeder;
use Database\Seeders\V1\Ownership\DefaultOwnershipSeeder;
use Database\Seeders\V1\Setting\SettingModuleSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * 
     * This seeder only runs essential seeders:
     * - Permissions
     * - Roles (including Super Admin)
     * - Super Admin User
     * - Settings (System-wide settings)
     * 
     * Other seeders (Users, Ownerships, etc.) are kept but must be run explicitly:
     * - php artisan db:seed --class="Database\Seeders\V1\Auth\AuthModuleSeeder"
     * - php artisan db:seed --class="Database\Seeders\V1\Ownership\OwnershipModuleSeeder"
     * etc.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting database seeding...');
        $this->command->info('');

        // Step 1: Seed Permissions
        $this->command->info('Step 1: Seeding permissions...');
        $this->call(PermissionSeeder::class);
        $this->command->info('');

        // Step 2: Seed Roles (including Super Admin)
        $this->command->info('Step 2: Seeding roles...');
        $this->call(RoleSeeder::class);
        $this->command->info('');

        // Step 3: Create Super Admin User
        $this->command->info('Step 3: Creating Super Admin user...');
        $this->createSuperAdmin();
        $this->command->info('');

        // Step 4: Seed Settings (System-wide settings)
        $this->command->info('Step 4: Seeding settings...');
        $this->call(SettingModuleSeeder::class);
        $this->command->info('');

        // Step 5: Create Default Ownership and link Super Admin
        $this->command->info('Step 5: Creating default ownership...');
        $this->call(DefaultOwnershipSeeder::class);
        $this->command->info('');

        $this->command->info('✅ Essential seeding completed!');
        $this->command->info('');
        $this->command->info('💡 To run other seeders, use:');
        $this->command->info('   - Auth Module: php artisan db:seed --class="Database\Seeders\V1\Auth\AuthModuleSeeder"');
        $this->command->info('   - Ownership Module: php artisan db:seed --class="Database\Seeders\V1\Ownership\OwnershipModuleSeeder"');
        $this->command->info('   - Phase 1 Module: php artisan db:seed --class="Database\Seeders\V1\Phase1\Phase1ModuleSeeder"');
        $this->command->info('   - Notification Module: php artisan db:seed --class="Database\Seeders\V1\Notification\NotificationModuleSeeder"');
    }

    /**
     * Create Super Admin user
     */
    private function createSuperAdmin(): void
    {
        // Get repository from service container
        $userRepository = app(UserRepositoryInterface::class);

        // Get Super Admin role (must use withSystemRoles to bypass global scope)
        $superAdminRole = Role::withSystemRoles()->where('name', 'Super Admin')->first();

        if (!$superAdminRole) {
            $this->command->error('Super Admin role not found. Please run RoleSeeder first.');
            return;
        }

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
            $this->command->info('✓ Super Admin created: admin@owners.com (password: password)');
        } else {
            if (!$superAdmin->hasRole('Super Admin')) {
                $superAdmin->assignRole('Super Admin');
            }
            $this->command->info('✓ Super Admin already exists: admin@owners.com');
        }
    }
}
