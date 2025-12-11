<?php

namespace Database\Seeders\V1\Ownership;

use Illuminate\Database\Seeder;

class OwnershipModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder runs all ownership module seeders in the correct order.
     */
    public function run(): void
    {
        $this->command->info('Starting Ownership Module seeding...');
        $this->command->info('');

        // Seed Bumahriz Center (مركز بامحرز) - Complete cycle
        $this->command->info('Seeding Bumahriz Center (مركز بامحرز)...');
        $this->call(BumahrizCenterSeeder::class);
        $this->command->info('');

        $this->command->info('✅ Ownership Module seeding completed successfully!');
    }
}
