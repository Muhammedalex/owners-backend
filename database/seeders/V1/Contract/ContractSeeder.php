<?php

namespace Database\Seeders\V1\Contract;

use App\Models\V1\Contract\Contract;
use App\Models\V1\Contract\ContractTerm;
use App\Models\V1\Ownership\Unit;
use App\Models\V1\Tenant\Tenant;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ownershipRepository = app(OwnershipRepositoryInterface::class);
        $ownerships = $ownershipRepository->all();

        if ($ownerships->isEmpty()) {
            $this->command->warn('No ownerships found. Please run OwnershipSeeder first.');
            return;
        }

        $this->command->info('Creating contracts for ' . $ownerships->count() . ' ownerships...');
        $this->command->info('');

        foreach ($ownerships as $ownership) {
            $this->command->info("Processing ownership: {$ownership->name}");

            // Get units for this ownership
            $units = Unit::where('ownership_id', $ownership->id)->get();
            $this->command->info("  Found {$units->count()} units for ownership {$ownership->id}");

            if ($units->isEmpty()) {
                $this->command->warn("  No units found for ownership: {$ownership->name}");
                continue;
            }

            // Get tenants for this ownership
            $tenants = Tenant::where('ownership_id', $ownership->id)->get();
            $this->command->info("  Found {$tenants->count()} tenants for ownership {$ownership->id}");

            if ($tenants->isEmpty()) {
                $this->command->warn("  No tenants found for ownership: {$ownership->name}");
                continue;
            }

            // Create 1-3 contracts per ownership
            $contractsCount = min(rand(1, 3), $tenants->count());
            $this->command->info("  Target contracts to create: {$contractsCount}");
            $selectedTenants = $tenants->random($contractsCount);

            foreach ($selectedTenants as $index => $tenant) {
                // For each tenant, pick 1-3 units (if available) to simulate multi-unit contracts
                $maxUnitsPerContract = min(3, $units->count());
                $this->command->info("    Iteration {$index}: maxUnitsPerContract={$maxUnitsPerContract} (units left: {$units->count()})");

                if ($maxUnitsPerContract === 0) {
                    $this->command->warn("    No units left to assign for contracts in ownership: {$ownership->name}");
                    break;
                }

                $unitsCountForContract = rand(1, $maxUnitsPerContract);
                $contractUnits = $units->random($unitsCountForContract);
                $primaryUnit = $contractUnits->first();

                $this->command->info("    Selected {$unitsCountForContract} units for new contract. Primary unit ID: {$primaryUnit->id}");

                $startDate = now()->subMonths(rand(0, 12));
                $endDate = $startDate->copy()->addYears(rand(1, 3));
                $rent = $primaryUnit->price_monthly ?? rand(3000, 15000);
                $deposit = $rent * rand(2, 4); // 2-4 months deposit

                $contract = Contract::create([
                    'uuid' => (string) Str::uuid(),
                    'unit_id' => $primaryUnit->id, // legacy single unit reference
                    'tenant_id' => $tenant->id,
                    'ownership_id' => $ownership->id,
                    'number' => $this->generateContractNumber($ownership->id, $index + 1),
                    'version' => 1,
                    'parent_id' => null,
                    'ejar_code' => rand(0, 1) ? $this->generateEjarCode() : null, // 50% chance
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'rent' => $rent,
                    'payment_frequency' => $this->getRandomPaymentFrequency(),
                    'deposit' => $deposit,
                    'deposit_status' => $this->getRandomDepositStatus(),
                    'document' => null,
                    'signature' => null,
                    'status' => $this->getRandomContractStatus(),
                    'created_by' => null,
                    'approved_by' => null,
                ]);

                $this->command->info("    Created contract ID {$contract->id} with number {$contract->number}");

                // Attach units to contract (pivot)
                $unitIds = $contractUnits->pluck('id')->all();
                $this->command->info('    Syncing units to contract_units pivot: [' . implode(', ', $unitIds) . ']');
                $contract->units()->sync($unitIds);
                $this->command->info('    Synced ' . count($unitIds) . ' units to contract_units for contract ID ' . $contract->id);

                // Create contract terms
                $this->createContractTerms($contract);

                // Update units status to rented
                foreach ($contractUnits as $unit) {
                    $unit->update(['status' => 'rented']);
                }

                $unitsNumbers = $contractUnits->pluck('number')->implode(', ');
                $this->command->info("  ✓ Created contract: {$contract->number} for units {$unitsNumbers}");

                // Remove used units from pool to avoid reusing them in other contracts
                $units = $units->whereNotIn('id', $contractUnits->pluck('id'));
                if ($units->isEmpty()) {
                    $this->command->warn("    Units pool exhausted for ownership: {$ownership->name}");
                    break;
                }
            }

            $this->command->info("  ✓ Completed contracts for {$ownership->name}");
            $this->command->info('');
        }

        $this->command->info('✅ Contracts seeded successfully!');
    }

    /**
     * Generate contract number
     */
    private function generateContractNumber(int $ownershipId, int $index): string
    {
        return 'CNT-' . str_pad($ownershipId, 3, '0', STR_PAD_LEFT) . '-' . date('Y') . '-' . str_pad($index, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Ejar code
     */
    private function generateEjarCode(): string
    {
        return 'EJAR' . rand(100000, 999999);
    }

    /**
     * Get random payment frequency
     */
    private function getRandomPaymentFrequency(): string
    {
        $frequencies = ['monthly', 'quarterly', 'yearly', 'weekly'];
        $weights = ['monthly' => 7, 'quarterly' => 2, 'yearly' => 1, 'weekly' => 0];
        return $this->getWeightedRandom($weights);
    }

    /**
     * Get random deposit status
     */
    private function getRandomDepositStatus(): string
    {
        $statuses = ['pending', 'paid', 'refunded', 'forfeited'];
        $weights = ['paid' => 6, 'pending' => 2, 'refunded' => 1, 'forfeited' => 1];
        return $this->getWeightedRandom($weights);
    }

    /**
     * Get random contract status
     */
    private function getRandomContractStatus(): string
    {
        $statuses = ['draft', 'pending', 'active', 'expired', 'terminated', 'cancelled'];
        $weights = ['active' => 5, 'pending' => 2, 'draft' => 1, 'expired' => 1, 'terminated' => 1, 'cancelled' => 0];
        return $this->getWeightedRandom($weights);
    }

    /**
     * Get weighted random value
     */
    private function getWeightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        
        $current = 0;
        foreach ($weights as $key => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return $key;
            }
        }
        
        return array_key_first($weights);
    }

    /**
     * Create contract terms
     */
    private function createContractTerms(Contract $contract): void
    {
        $terms = [
            [
                'key' => 'maintenance_responsibility',
                'value' => 'Tenant is responsible for minor maintenance. Owner handles major repairs.',
                'type' => 'text',
            ],
            [
                'key' => 'utilities',
                'value' => 'Tenant pays for electricity, water, and internet. Owner covers building maintenance fees.',
                'type' => 'text',
            ],
            [
                'key' => 'pets_allowed',
                'value' => rand(0, 1) ? 'true' : 'false',
                'type' => 'boolean',
            ],
            [
                'key' => 'notice_period',
                'value' => (string) rand(30, 90),
                'type' => 'integer',
            ],
            [
                'key' => 'renewal_option',
                'value' => rand(0, 1) ? 'true' : 'false',
                'type' => 'boolean',
            ],
        ];

        foreach ($terms as $term) {
            ContractTerm::create([
                'contract_id' => $contract->id,
                'key' => $term['key'],
                'value' => $term['value'],
                'type' => $term['type'],
            ]);
        }
    }
}

