<?php

namespace Database\Seeders\V1\Setting;

use Illuminate\Database\Seeder;

class SettingModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder runs all settings module seeders in the correct order.
     */
    public function run(): void
    {
        $this->command->info('Starting Settings Module seeding...');
        $this->command->info('');

        // Step 1: Seed System-wide settings (Super Admin only)
        $this->command->info('Step 1: Seeding system-wide settings...');
        $this->call(SystemSettingSeeder::class);
        $this->command->info('');

        $this->command->info('✅ Settings Module seeding completed successfully!');
    }
}

