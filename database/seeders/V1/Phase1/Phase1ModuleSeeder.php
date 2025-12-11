<?php

namespace Database\Seeders\V1\Phase1;

use Illuminate\Database\Seeder;

class Phase1ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder runs all Phase 1 module seeders in the correct order.
     * Phase 1 includes: Tenants, Contracts, Invoices, and Payments.
     *
     * Prerequisites:
     * - Ownerships must be seeded (OwnershipSeeder)
     * - Property structure must be seeded (PropertyStructureSeeder)
     * - Users must be seeded (UserSeeder)
     */
    public function run(): void
    {
        $this->command->info('Starting Phase 1 Module seeding...');
        $this->command->info('');
        $this->command->info('Phase 1 data is already seeded in BumahrizCenterSeeder (Tenants, Contracts, Invoices, Payments)');
        $this->command->info('');

        $this->command->info('✅ Phase 1 Module seeding completed successfully!');
    }
}

