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

        // Step 1: Seed Ownerships (with user mappings and board members)
        $this->command->info('Step 1: Seeding ownerships...');
        $this->call(OwnershipSeeder::class);
        $this->command->info('');

        // Step 2: Seed Property Structure (portfolios, buildings, floors, units)
        $this->command->info('Step 2: Seeding property structure...');
        $this->call(PropertyStructureSeeder::class);
        $this->command->info('');

        $this->command->info('✅ Ownership Module seeding completed successfully!');
    }
}
