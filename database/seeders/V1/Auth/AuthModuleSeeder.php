<?php

namespace Database\Seeders\V1\Auth;

use Illuminate\Database\Seeder;

class AuthModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder runs all auth module seeders in the correct order.
     */
    public function run(): void
    {
        $this->command->info('Starting Auth Module seeding...');
        $this->command->info('');

        // Step 1: Seed Permissions
        $this->command->info('Step 1: Seeding permissions...');
        $this->call(PermissionSeeder::class);
        $this->command->info('');

        // Step 2: Seed Roles
        $this->command->info('Step 2: Seeding roles...');
        $this->call(RoleSeeder::class);
        $this->command->info('');

        // Step 3: Seed Users
        $this->command->info('Step 3: Seeding users...');
        $this->call(UserSeeder::class);
        $this->command->info('');

        $this->command->info('✅ Auth Module seeding completed successfully!');
    }
}
