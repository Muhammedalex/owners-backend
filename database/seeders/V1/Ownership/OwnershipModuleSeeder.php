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

        // Seed Shared Users (used by both ownerships)
        $this->command->info('Seeding Shared Users...');
        $this->call(SharedUsersSeeder::class);
        $this->command->info('');

        // Seed Bumahriz Center (مركز بامحرز) - Complete cycle
        $this->command->info('Seeding Bumahriz Center (مركز بامحرز)...');
        $this->call(BumahrizCenterSeeder::class);
        $this->command->info('');

        // Seed Al Noor Tower (برج النور) - Complete cycle
        $this->command->info('Seeding Al Noor Tower (برج النور)...');
        $this->call(AlNoorTowerSeeder::class);
        $this->command->info('');

        $this->command->info('✅ Ownership Module seeding completed successfully!');
    }
}
