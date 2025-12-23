<?php

namespace Database\Seeders\V1\Ownership;

use App\Models\V1\Auth\Role;
use App\Models\V1\Auth\User;
use App\Models\V1\Contract\Contract;
use App\Models\V1\Contract\ContractTerm;
use App\Enums\V1\Invoice\InvoiceStatus;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Invoice\InvoiceItem;
use App\Models\V1\Ownership\Building;
use App\Models\V1\Ownership\BuildingFloor;
use App\Models\V1\Ownership\Ownership;
use App\Models\V1\Ownership\Portfolio;
use App\Models\V1\Ownership\PortfolioLocation;
use App\Models\V1\Ownership\Unit;
use App\Models\V1\Ownership\UnitSpecification;
use App\Models\V1\Payment\Payment;
use App\Models\V1\Tenant\Tenant;
use App\Services\V1\Invoice\ContractInvoiceService;
use Carbon\Carbon;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\OwnershipBoardMemberRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\UserOwnershipMappingRepositoryInterface;
use App\Services\V1\Contract\ContractService;
use App\Services\V1\Ownership\OwnershipService;
use App\Services\V1\Tenant\TenantService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BumahrizCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏢 Starting Bumahriz Center (مركز بامحرز) seeding...');
        $this->command->info('');

        DB::transaction(function () {
            // Step 1: Get or create Super Admin
            $superAdmin = $this->getOrCreateSuperAdmin();

            // Step 2: Create Shared Users (if not already created)
            $sharedUsersSeeder = new SharedUsersSeeder();
            $sharedUsersSeeder->setCommand($this->command);
            $sharedUsersSeeder->run();

            // Step 3: Get shared users
            $userRepository = app(UserRepositoryInterface::class);
            $owner = $userRepository->findByEmail('salem@owners.com');
            // Use first 2 tenants for Bumahriz (as originally designed)
            $tenants = [];
            for ($i = 1; $i <= 2; $i++) {
                $tenant = $userRepository->findByEmail("tenant{$i}@owners.com");
                if ($tenant) {
                    $tenants[] = $tenant;
                }
            }

            // Step 4: Create Ownership (مركز بامحرز)
            $ownership = $this->createOwnership($superAdmin);

            // Step 5: Map all shared users to this ownership
            $this->mapUsersToOwnership($ownership, $owner, $tenants);

            // Step 6: Create Property Structure (Portfolio, Building, Floors, Units)
            $this->createPropertyStructure($ownership);

            // Step 7: Create Tenant Records
            $this->createTenantRecords($ownership, $tenants);

            // Step 8: Create Contracts
            $this->createContracts($ownership, $tenants);

            // Step 9: Create Invoices and Payments
            $this->createInvoicesAndPayments($ownership);

            $this->command->info('');
            $this->command->info('✅ Bumahriz Center seeding completed successfully!');
        });
    }

    /**
     * Get or create Super Admin user.
     */
    private function getOrCreateSuperAdmin(): User
    {
        $userRepository = app(UserRepositoryInterface::class);
        $superAdmin = $userRepository->findByEmail('admin@owners.com');

        if (!$superAdmin) {
            $superAdminRole = Role::withSystemRoles()->where('name', 'Super Admin')->first();
            if (!$superAdminRole) {
                $this->command->error('Super Admin role not found. Please run RoleSeeder first.');
                throw new \Exception('Super Admin role not found');
            }

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
        }

        return $superAdmin;
    }

    /**
     * Create Ownership (مركز بامحرز).
     */
    private function createOwnership(User $superAdmin): Ownership
    {
        $ownershipService = app(OwnershipService::class);

        $ownershipData = [
            'uuid' => '550e8400-e29b-41d4-a716-446655440001',
            'name' => 'مركز بامحرز',
            'legal' => 'مركز بامحرز - سوق الجنوبية',
            'type' => 'company',
            'ownership_type' => 'commercial',
            'registration' => 'CR7001234567',
            'tax_id' => '300123456700003',
            'street' => 'شارع بامحرز',
            'city' => 'Jeddah',
            'state' => 'Makkah Province',
            'country' => 'Saudi Arabia',
            'zip_code' => '21432',
            'email' => 'info@bumahriz.com',
            'phone' => '+966126486483',
            'active' => true,
            'created_by' => $superAdmin->id,
        ];

        // Check if ownership already exists
        $existingOwnership = Ownership::where('uuid', $ownershipData['uuid'])->first();
        if ($existingOwnership) {
            $this->command->info('✓ Ownership already exists: ' . $ownershipData['name']);
            return $existingOwnership;
        }

        // Create ownership (this will automatically create portfolio, building, and settings)
        $ownership = $ownershipService->create($ownershipData);
        $this->command->info('✓ Created ownership: ' . $ownership->name);

        return $ownership;
    }

    /**
     * Map all shared users to ownership.
     */
    private function mapUsersToOwnership(Ownership $ownership, User $owner, array $tenants): void
    {
        $mappingRepository = app(UserOwnershipMappingRepositoryInterface::class);
        $boardMemberRepository = app(OwnershipBoardMemberRepositoryInterface::class);
        $userRepository = app(UserRepositoryInterface::class);

        // Map owner
        $mapping = $mappingRepository->findByUserAndOwnership($owner->id, $ownership->id);
        if (!$mapping) {
            $mappingRepository->create([
                'user_id' => $owner->id,
                'ownership_id' => $ownership->id,
                'default' => true, // Default for Bumahriz
            ]);
            $this->command->info('  → Mapped owner to ownership');
        }

        // Add owner as board member
        $boardMember = $boardMemberRepository->findByOwnershipAndUser($ownership->id, $owner->id);
        if (!$boardMember) {
            $boardMemberRepository->create([
                'ownership_id' => $ownership->id,
                'user_id' => $owner->id,
                'role' => 'Chairman',
                'active' => true,
                'start_date' => now()->subYears(5),
            ]);
            $this->command->info('  → Added owner as board member (Chairman)');
        }

        // Map all role users
        $roleUsers = [
            'board.member@owners.com',
            'moderator@owners.com',
            'accountant@owners.com',
            'property.manager@owners.com',
            'maintenance.manager@owners.com',
            'facility.manager@owners.com',
            'collector1@owners.com',
            'collector2@owners.com',
            'collector3@owners.com',
        ];

        foreach ($roleUsers as $email) {
            $user = $userRepository->findByEmail($email);
            if ($user) {
                $mapping = $mappingRepository->findByUserAndOwnership($user->id, $ownership->id);
                if (!$mapping) {
                    $mappingRepository->create([
                        'user_id' => $user->id,
                        'ownership_id' => $ownership->id,
                        'default' => false,
                    ]);
                }
            }
        }

        // Map tenants
        foreach ($tenants as $tenant) {
            if ($tenant) {
                $mapping = $mappingRepository->findByUserAndOwnership($tenant->id, $ownership->id);
                if (!$mapping) {
                    $mappingRepository->create([
                        'user_id' => $tenant->id,
                        'ownership_id' => $ownership->id,
                        'default' => false,
                    ]);
                }
            }
        }

        $this->command->info('  → Mapped all shared users to ownership');
    }

    /**
     * Create Property Structure (Portfolio, Building, Floors, Units).
     */
    private function createPropertyStructure(Ownership $ownership): void
    {
        $this->command->info('  Creating property structure...');

        // Get the default portfolio (created by OwnershipService)
        $portfolio = Portfolio::where('ownership_id', $ownership->id)->first();
        if (!$portfolio) {
            $this->command->error('  Portfolio not found. This should not happen.');
            return;
        }

        // Update portfolio name and details
        $portfolio->update([
            'name' => 'سوق الجنوبية - مركز بامحرز',
            'type' => 'commercial',
            'description' => 'سوق تجاري يحتوي على محلات متنوعة (ملابس، أدوات كهربائية، مطرزات وأقمشة)',
            'area' => 5000.00, // 5000 sqm
        ]);

        // Add portfolio location
        $location = PortfolioLocation::where('portfolio_id', $portfolio->id)->first();
        if (!$location) {
            PortfolioLocation::create([
                'portfolio_id' => $portfolio->id,
                'street' => 'شارع بامحرز',
                'city' => 'Jeddah',
                'state' => 'Makkah Province',
                'country' => 'Saudi Arabia',
                'zip_code' => '21432',
                'latitude' => 21.4858, // Jeddah coordinates
                'longitude' => 39.1925,
                'primary' => true,
            ]);
        }

        // Get the default building (created by OwnershipService)
        $building = Building::where('portfolio_id', $portfolio->id)->first();
        if (!$building) {
            $this->command->error('  Building not found. This should not happen.');
            return;
        }

        // Update building details for commercial market
        $building->update([
            'name' => 'مبنى سوق الجنوبية',
            'type' => 'commercial',
            'description' => 'مبنى سوق تجاري يحتوي على طابقين',
            'street' => 'شارع بامحرز',
            'city' => 'Jeddah',
            'state' => 'Makkah Province',
            'country' => 'Saudi Arabia',
            'zip_code' => '21432',
            'latitude' => 21.4858,
            'longitude' => 39.1925,
            'floors' => 2,
            'year' => 2015,
        ]);

        $this->command->info('    ✓ Updated portfolio and building');

        // Create floors (Ground Floor + First Floor)
        $floors = [];
        for ($f = 1; $f <= 2; $f++) {
            $floor = BuildingFloor::where('building_id', $building->id)
                ->where('number', $f)
                ->first();

            if (!$floor) {
                $floor = BuildingFloor::create([
                    'building_id' => $building->id,
                    'number' => $f,
                    'name' => $f === 1 ? 'الطابق الأرضي' : 'الطابق الأول',
                    'description' => $f === 1 ? 'الطابق الأرضي - محلات تجارية' : 'الطابق الأول - محلات تجارية',
                    'units' => $f === 1 ? 15 : 12, // Ground floor: 15 shops, First floor: 12 shops
                    'active' => true,
                ]);
            }
            $floors[] = $floor;
            $this->command->info("    ✓ Created/Updated floor: {$floor->name}");
        }

        // Create units (shops) for each floor
        $shopTypes = ['clothing', 'electronics', 'fabric', 'accessories', 'general'];
        $shopNames = [
            'محل الملابس الرجالية',
            'محل الأقمشة والمطرزات',
            'محل الأدوات الكهربائية',
            'محل الإكسسوارات',
            'محل عام',
            'محل الأحذية',
            'محل العطور',
            'محل الأجهزة الإلكترونية',
        ];

        $unitIndex = 1;
        foreach ($floors as $floor) {
            $unitsCount = $floor->number === 1 ? 15 : 12; // Ground: 15, First: 12

            for ($u = 1; $u <= $unitsCount; $u++) {
                $unitNumber = $this->generateUnitNumber($floor->number, $u);
                $shopType = $shopTypes[($u - 1) % count($shopTypes)];
                $shopName = $shopNames[($u - 1) % count($shopNames)] . ' ' . $unitNumber;
                $area = rand(20, 80); // Shop area: 20-80 sqm
                $pricePerSqm = rand(80, 150); // 80-150 SAR per sqm per month
                $monthly = $area * $pricePerSqm;
                $quarterly = $monthly * 3 * 0.95; // 5% discount
                $yearly = $monthly * 12 * 0.90; // 10% discount

                $unit = Unit::where('building_id', $building->id)
                    ->where('floor_id', $floor->id)
                    ->where('number', $unitNumber)
                    ->first();

                if (!$unit) {
                    $unit = Unit::create([
                        'uuid' => (string) Str::uuid(),
                        'building_id' => $building->id,
                        'floor_id' => $floor->id,
                        'ownership_id' => $ownership->id,
                        'number' => $unitNumber,
                        'type' => 'shop',
                        'name' => $shopName,
                        'description' => "محل تجاري في {$floor->name} - {$shopType}",
                        'area' => $area,
                        'price_monthly' => round($monthly, 2),
                        'price_quarterly' => round($quarterly, 2),
                        'price_yearly' => round($yearly, 2),
                        'status' => 'available', // First 2 shops are rented
                        'active' => true,
                    ]);

                    // Create unit specifications for shop
                    UnitSpecification::create([
                        'unit_id' => $unit->id,
                        'key' => 'storefront',
                        'value' => 'true',
                        'type' => 'boolean',
                    ]);
                    UnitSpecification::create([
                        'unit_id' => $unit->id,
                        'key' => 'parking',
                        'value' => (string) rand(0, 2),
                        'type' => 'integer',
                    ]);
                    UnitSpecification::create([
                        'unit_id' => $unit->id,
                        'key' => 'storage',
                        'value' => rand(0, 1) ? 'true' : 'false',
                        'type' => 'boolean',
                    ]);
                }
                $unitIndex++;
            }
            $this->command->info("    ✓ Created/Updated {$unitsCount} units for {$floor->name}");
        }

        $this->command->info('  ✓ Property structure completed');
    }

    /**
     * Create Tenant Records.
     */
    private function createTenantRecords(Ownership $ownership, array $tenantUsers): void
    {
        $this->command->info('  Creating tenant records...');

        $tenantService = app(TenantService::class);

        foreach ($tenantUsers as $index => $user) {
            $tenant = Tenant::where('user_id', $user->id)
                ->where('ownership_id', $ownership->id)
                ->first();

            if (!$tenant) {
                $tenant = $tenantService->create([
                    'user_id' => $user->id,
                    'ownership_id' => $ownership->id,
                    'national_id' => $this->generateNationalId(),
                    'id_type' => 'national_id',
                    'id_document' => null,
                    'id_expiry' => now()->addYears(rand(3, 7)),
                    'emergency_name' => $this->getRandomName(),
                    'emergency_phone' => $this->generatePhoneNumber(),
                    'emergency_relation' => $this->getRandomRelation(),
                    'employment' => 'self_employed',
                    'employer' => null,
                    'income' => rand(10000, 50000) + (rand(0, 99) / 100),
                    'rating' => 'good',
                    'notes' => 'مستأجر في مركز بامحرز',
                ]);
                $this->command->info("    ✓ Created tenant record for: {$user->email}");
            } else {
                $this->command->info("    ✓ Tenant record already exists for: {$user->email}");
            }
        }

        $this->command->info('  ✓ Tenant records completed');
    }

    /**
     * Create Contracts.
     */
    private function createContracts(Ownership $ownership, array $tenantUsers): void
    {
        $this->command->info('  Creating contracts...');

        $contractService = app(ContractService::class);

        // Get available units
        $units = Unit::where('ownership_id', $ownership->id)
            ->where('status', 'available')
            ->get();

        if ($units->isEmpty()) {
            $this->command->warn('    No available units found. Skipping contracts...');
            return;
        }

        // Get tenant records
        $tenants = Tenant::where('ownership_id', $ownership->id)->get();
        if ($tenants->isEmpty()) {
            $this->command->warn('    No tenant records found. Skipping contracts...');
            return;
        }

        // Create 2 contracts (one for each tenant)
        $contractsCount = min(2, min($units->count(), $tenants->count()));
        $selectedUnits = $units->take($contractsCount);
        $selectedTenants = $tenants->take($contractsCount);

        foreach ($selectedUnits as $index => $unit) {
            $tenant = $selectedTenants[$index];
            
            // Contract dates: Start from 2025-01-01 or earlier, end by 2025-12-31 or later
            $startDate = Carbon::create(2025, 1, 1)->subMonths(rand(0, 3)); // Can start before 2025
            $endDate = Carbon::create(2025, 12, 31)->addMonths(rand(0, 6)); // Can end after 2025
            
            $baseRent = $unit->price_monthly ?? rand(5000, 15000);
            $deposit = $baseRent * 3; // 3 months deposit
            
            // Calculate total_rent based on contract duration
            $contractMonths = $startDate->diffInMonths($endDate);
            $totalRent = round(($baseRent * 12) * ($contractMonths / 12), 2);
            $vatAmount = round($totalRent * 0.15, 2); // 15% VAT
            $totalRentWithVat = $totalRent + $vatAmount;

            // Random payment frequency (weighted towards monthly)
            $frequencies = ['monthly', 'quarterly', 'yearly'];
            $weights = ['monthly' => 6, 'quarterly' => 3, 'yearly' => 1];
            $paymentFrequency = $this->getWeightedRandom($frequencies, $weights);

            // Check if contract already exists for this unit and tenant
            $existingContract = Contract::whereHas('units', function ($query) use ($unit) {
                $query->where('units.id', $unit->id);
            })
                ->where('tenant_id', $tenant->id)
                ->where('ownership_id', $ownership->id)
                ->first();

            if (!$existingContract) {
                // Use ContractService to create contract with new structure
                $contractData = [
                    'uuid' => (string) Str::uuid(), // Explicitly set UUID
                    'tenant_id' => $tenant->id,
                    'ownership_id' => $ownership->id,
                    'number' => $this->generateContractNumber($ownership->id, $index + 1),
                    'version' => 1,
                    'parent_id' => null,
                    'ejar_code' => rand(0, 1) ? $this->generateEjarCode() : null, // 50% chance
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'base_rent' => $baseRent,
                    'rent_fees' => 0,
                    'vat_amount' => $vatAmount,
                    'total_rent' => $totalRentWithVat,
                    'payment_frequency' => $paymentFrequency,
                    'deposit' => $deposit,
                    'deposit_status' => 'paid',
                    'created_by' => $ownership->created_by,
                    // Status will be set by service based on settings
                    // Units will be synced by service
                    'units' => [
                        [
                            'unit_id' => $unit->id,
                            'rent_amount' => $baseRent,
                            'notes' => null,
                        ],
                    ],
                ];

                $contract = $contractService->create($contractData);

                // If contract status is not active (draft or pending), approve it to make it active
                if (in_array($contract->status, ['draft', 'pending'])) {
                    try {
                        $contract = $contractService->approve($contract, $ownership->created_by);
                    } catch (\Exception $e) {
                        $this->command->warn("    ⚠ Could not approve contract {$contract->number}: {$e->getMessage()}");
                    }
                }

                // Create contract terms
                $this->createContractTerms($contract);

                $this->command->info("    ✓ Created contract: {$contract->number} for unit {$unit->number} (Status: {$contract->status})");
            } else {
                $this->command->info("    ✓ Contract already exists for unit {$unit->number}");
            }
        }

        $this->command->info('  ✓ Contracts completed');
    }

    /**
     * Create Contract Terms.
     */
    private function createContractTerms(Contract $contract): void
    {
        $terms = [
            [
                'key' => 'maintenance_responsibility',
                'value' => 'المستأجر مسؤول عن الصيانة البسيطة. المالك مسؤول عن الصيانة الكبرى.',
                'type' => 'text',
            ],
            [
                'key' => 'utilities',
                'value' => 'المستأجر مسؤول عن فواتير الكهرباء والمياه',
                'type' => 'text',
            ],
            [
                'key' => 'insurance',
                'value' => 'المستأجر مسؤول عن تأمين المحل',
                'type' => 'text',
            ],
        ];

        foreach ($terms as $term) {
            ContractTerm::firstOrCreate(
                [
                    'contract_id' => $contract->id,
                    'key' => $term['key'],
                ],
                $term
            );
        }
    }

    /**
     * Create Invoices and Payments.
     * Creates realistic invoices from 2025-01-01 to 2025-12-31
     * Based on contract payment_frequency with various statuses and payments.
     */
    private function createInvoicesAndPayments(Ownership $ownership): void
    {
        $this->command->info('  Creating invoices and payments...');

        // Get active contracts
        $contracts = Contract::where('ownership_id', $ownership->id)
            ->where('status', 'active')
            ->get();

        if ($contracts->isEmpty()) {
            $this->command->warn('    No active contracts found. Skipping invoices...');
            return;
        }

        $contractInvoiceService = app(ContractInvoiceService::class);
        $invoiceCounter = 1;
        $now = Carbon::now();
        $yearStart = Carbon::create(2025, 1, 1)->startOfDay();
        $yearEnd = Carbon::create(2025, 12, 31)->endOfDay();

        foreach ($contracts as $contract) {
            $this->command->info("    Processing contract: {$contract->number} (Frequency: {$contract->payment_frequency})");

            // Get contract period (intersect with 2025)
            $contractStart = Carbon::parse($contract->start);
            $contractEnd = Carbon::parse($contract->end);
            
            // Start from max(contract_start, year_start)
            $periodStart = $contractStart->gt($yearStart) ? $contractStart : $yearStart;
            
            // Don't create invoices if contract starts after year end
            if ($periodStart->gt($yearEnd)) {
                $this->command->warn("      Contract starts after 2025, skipping...");
                continue;
            }

            // Get period increment based on payment_frequency
            $monthsPerPeriod = $this->getMonthsForFrequency($contract->payment_frequency);
            
            // Generate invoices for the entire year (or contract period if shorter)
            $currentPeriodStart = $periodStart->copy();

            while ($currentPeriodStart->lte($yearEnd) && $currentPeriodStart->lte($contractEnd)) {
                // Calculate period end based on frequency
                if ($contract->payment_frequency === 'weekly') {
                    // Weekly: 4 weeks per period
                    $currentPeriodEnd = $currentPeriodStart->copy()->addWeeks(4)->subDay();
                } else {
                    // Monthly, quarterly, yearly, etc.
                    $currentPeriodEnd = $currentPeriodStart->copy()->addMonths($monthsPerPeriod)->subDay();
                }
                
                // Cap by contract end and year end
                if ($currentPeriodEnd->gt($contractEnd)) {
                    $currentPeriodEnd = $contractEnd->copy();
                }
                if ($currentPeriodEnd->gt($yearEnd)) {
                    $currentPeriodEnd = $yearEnd->copy();
                }

                // Skip if period is invalid
                if ($currentPeriodStart->gte($currentPeriodEnd)) {
                    break;
                }

                // Check if invoice already exists for this period
                $existingInvoice = Invoice::where('contract_id', $contract->id)
                    ->where('period_start', $currentPeriodStart->format('Y-m-d'))
                    ->where('period_end', $currentPeriodEnd->format('Y-m-d'))
                    ->first();

                if ($existingInvoice) {
                    $this->command->info("      ✓ Invoice already exists for period {$currentPeriodStart->format('Y-m-d')} to {$currentPeriodEnd->format('Y-m-d')}");
                    $currentPeriodStart = $currentPeriodEnd->copy()->addDay();
                    continue;
                }

                // Calculate due date (7-15 days after period end)
                $dueDate = $currentPeriodEnd->copy()->addDays(rand(7, 15));

                // Generate invoice number
                $invoiceNumber = $this->generateInvoiceNumber($ownership->id, $invoiceCounter++);

                // Calculate amount from contract
                $amount = $contractInvoiceService->calculateAmountFromContract($contract, [
                    'start' => $currentPeriodStart->format('Y-m-d'),
                    'end' => $currentPeriodEnd->format('Y-m-d'),
                ]);

                // Determine invoice status based on dates and randomness
                $status = $this->determineInvoiceStatus($dueDate, $now);

                // Create invoice
                $invoice = Invoice::create([
                    'uuid' => (string) Str::uuid(),
                    'contract_id' => $contract->id,
                    'ownership_id' => $ownership->id,
                    'number' => $invoiceNumber,
                    'period_start' => $currentPeriodStart->format('Y-m-d'),
                    'period_end' => $currentPeriodEnd->format('Y-m-d'),
                    'due' => $dueDate->format('Y-m-d'),
                    'amount' => $amount,
                    'tax_from_contract' => true, // Contract invoices include tax in total_rent
                    'tax' => null,
                    'tax_rate' => null,
                    'total' => $amount,
                    'status' => $status->value,
                    'notes' => $this->getRandomInvoiceNotes($contract->payment_frequency),
                    'generated_by' => $ownership->created_by,
                    'generated_at' => $currentPeriodStart->copy()->subDays(rand(0, 5)), // Generated a few days before period start
                    'paid_at' => ($status === InvoiceStatus::PAID || $status === InvoiceStatus::PARTIAL) 
                        ? $this->getPaidAtDate($dueDate, $now) 
                        : null,
                ]);

                // Create invoice items
                $this->createInvoiceItemsForContract($invoice, $contract);

                // Create payments based on invoice status
                if ($status === InvoiceStatus::PAID || $status === InvoiceStatus::PARTIAL) {
                    $this->createPaymentsForInvoice($invoice, $ownership, $status);
                }

                $this->command->info("      ✓ Created invoice: {$invoice->number} ({$status->value}) - {$currentPeriodStart->format('Y-m-d')} to {$currentPeriodEnd->format('Y-m-d')}");

                // Move to next period (always start from day after period end)
                $currentPeriodStart = $currentPeriodEnd->copy()->addDay();
            }
        }

        $this->command->info('  ✓ Invoices and payments completed');
    }

    /**
     * Get months for payment frequency.
     */
    private function getMonthsForFrequency(string $frequency): int
    {
        return match ($frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annually' => 6,
            'yearly' => 12,
            'weekly' => 0, // Weekly is handled separately (4 weeks)
            default => 1,
        };
    }

    /**
     * Determine invoice status based on dates and realistic scenarios.
     */
    private function determineInvoiceStatus(Carbon $dueDate, Carbon $now): InvoiceStatus
    {
        $daysPastDue = $now->diffInDays($dueDate, false);

        // Future invoices (due date in future)
        if ($daysPastDue > 0) {
            // 70% sent, 20% draft, 10% pending
            $rand = rand(1, 10);
            if ($rand <= 7) {
                return InvoiceStatus::SENT;
            } elseif ($rand <= 9) {
                return InvoiceStatus::DRAFT;
            } else {
                return InvoiceStatus::PENDING;
            }
        }

        // Past due invoices
        if ($daysPastDue < 0) {
            $daysOverdue = abs($daysPastDue);
            
            // Very overdue (> 30 days) - more likely to be overdue
            if ($daysOverdue > 30) {
                $rand = rand(1, 10);
                if ($rand <= 6) {
                    return InvoiceStatus::OVERDUE;
                } elseif ($rand <= 8) {
                    return InvoiceStatus::PARTIAL; // Some payment made
                } else {
                    return InvoiceStatus::PAID; // Finally paid
                }
            }
            
            // Recently overdue (1-30 days)
            $rand = rand(1, 10);
            if ($rand <= 4) {
                return InvoiceStatus::PAID; // Paid on time or slightly late
            } elseif ($rand <= 6) {
                return InvoiceStatus::PARTIAL; // Partial payment
            } elseif ($rand <= 8) {
                return InvoiceStatus::OVERDUE; // Overdue
            } else {
                return InvoiceStatus::SENT; // Still sent, not yet overdue
            }
        }

        // Due today
        $rand = rand(1, 10);
        if ($rand <= 5) {
            return InvoiceStatus::PAID; // Paid on time
        } elseif ($rand <= 7) {
            return InvoiceStatus::SENT; // Still sent
        } else {
            return InvoiceStatus::VIEWED; // Viewed but not paid yet
        }
    }

    /**
     * Get paid_at date for invoice.
     */
    private function getPaidAtDate(Carbon $dueDate, Carbon $now): ?Carbon
    {
        if ($dueDate->isPast()) {
            // Paid after due date (1-45 days late, or on time)
            $daysLate = rand(-5, 45); // Can be up to 5 days early or 45 days late
            return $dueDate->copy()->addDays($daysLate);
        } else {
            // Paid before due date (early payment)
            $daysEarly = rand(0, 10);
            return $dueDate->copy()->subDays($daysEarly);
        }
    }

    /**
     * Create invoice items for contract.
     */
    private function createInvoiceItemsForContract(Invoice $invoice, Contract $contract): void
    {
        $contract->loadMissing('units');

        if ($contract->units->isEmpty()) {
            // Single item based on contract
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'rent',
                'description' => 'إيجار - ' . $invoice->period_start . ' إلى ' . $invoice->period_end,
                'quantity' => 1,
                'unit_price' => $invoice->amount,
                'total' => $invoice->amount,
            ]);
        } else {
            // One item per unit
            $totalAmount = 0;
            foreach ($contract->units as $unit) {
                $pivotRent = $unit->pivot?->rent_amount;
                $unitRent = $pivotRent !== null 
                    ? (float) $pivotRent 
                    : (float) ($invoice->amount / max($contract->units->count(), 1));

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'type' => 'rent',
                    'description' => 'إيجار الوحدة ' . ($unit->number ?? $unit->id) . ' - ' . $invoice->period_start . ' إلى ' . $invoice->period_end,
                    'quantity' => 1,
                    'unit_price' => $unitRent,
                    'total' => $unitRent,
                ]);
                
                $totalAmount += $unitRent;
            }

            // Sometimes add service fee (30% chance)
            if (rand(1, 10) <= 3) {
                $serviceFee = round($invoice->amount * 0.05, 2); // 5% of rent
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'type' => 'service_fee',
                    'description' => 'رسوم صيانة وإدارة',
                    'quantity' => 1,
                    'unit_price' => $serviceFee,
                    'total' => $serviceFee,
                ]);
                
                // Update invoice total
                $invoice->update([
                    'amount' => $invoice->amount + $serviceFee,
                    'total' => $invoice->total + $serviceFee,
                ]);
            }
        }
    }

    /**
     * Create payments for invoice based on status.
     */
    private function createPaymentsForInvoice(Invoice $invoice, Ownership $ownership, InvoiceStatus $status): void
    {
        if ($status === InvoiceStatus::PAID) {
            // Full payment
            $this->createPayment($invoice, $ownership, $invoice->total, 'paid', $invoice->paid_at);
        } elseif ($status === InvoiceStatus::PARTIAL) {
            // Partial payment (50-90% of total)
            $paymentAmount = round($invoice->total * (rand(50, 90) / 100), 2);
            $this->createPayment($invoice, $ownership, $paymentAmount, 'paid', $invoice->paid_at);
            
            // Sometimes add second partial payment (20% chance)
            if (rand(1, 10) <= 2) {
                $remainingAmount = $invoice->total - $paymentAmount;
                $secondPaymentAmount = round($remainingAmount * (rand(30, 80) / 100), 2);
                $secondPaidAt = $invoice->paid_at 
                    ? Carbon::parse($invoice->paid_at)->addDays(rand(5, 20))
                    : null;
                $this->createPayment($invoice, $ownership, $secondPaymentAmount, 'paid', $secondPaidAt);
            }
        }
    }

    /**
     * Create a payment record.
     */
    private function createPayment(Invoice $invoice, Ownership $ownership, float $amount, string $status, ?Carbon $paidAt = null): void
    {
        $methods = ['cash', 'bank_transfer', 'check', 'visa', 'other'];
        $weights = ['bank_transfer' => 5, 'cash' => 3, 'check' => 1, 'visa' => 3, 'other' => 1];
        $method = $this->getWeightedRandom($methods, $weights);

        Payment::create([
            'uuid' => (string) Str::uuid(),
            'invoice_id' => $invoice->id,
            'ownership_id' => $ownership->id,
            'method' => $method,
            'transaction_id' => rand(1, 10) <= 8 ? 'TXN' . date('Ymd') . rand(100000, 999999) : null,
            'amount' => $amount,
            'currency' => 'SAR',
            'status' => $status,
            'paid_at' => $paidAt?->format('Y-m-d H:i:s'),
            'confirmed_by' => $ownership->created_by,
            'notes' => $this->getRandomPaymentNotes(),
        ]);
    }

    /**
     * Get weighted random value.
     */
    private function getWeightedRandom(array $values, array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        
        $current = 0;
        foreach ($values as $index => $value) {
            $weight = $weights[$value] ?? 1;
            $current += $weight;
            if ($rand <= $current) {
                return $value;
            }
        }
        
        return $values[0];
    }

    /**
     * Get random invoice notes.
     */
    private function getRandomInvoiceNotes(string $frequency): ?string
    {
        $notes = [
            'فاتورة إيجار ' . $this->getFrequencyLabel($frequency),
            'فاتورة إيجار محل تجاري',
            'فاتورة إيجار - ' . $this->getFrequencyLabel($frequency),
            null,
            null,
        ];
        
        return $notes[array_rand($notes)];
    }

    /**
     * Get frequency label in Arabic.
     */
    private function getFrequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            'monthly' => 'شهرية',
            'quarterly' => 'ربع سنوية',
            'semi_annually' => 'نصف سنوية',
            'yearly' => 'سنوية',
            'weekly' => 'أسبوعية',
            default => 'شهرية',
        };
    }

    /**
     * Get random payment notes.
     */
    private function getRandomPaymentNotes(): ?string
    {
        $notes = [
            'دفعة إيجار',
            'تحويل بنكي',
            'دفع نقدي',
            null,
            null,
        ];
        
        return $notes[array_rand($notes)];
    }

    /**
     * Generate unit number.
     */
    private function generateUnitNumber(int $floorNumber, int $unitIndex): string
    {
        $floorPrefix = str_pad($floorNumber, 2, '0', STR_PAD_LEFT);
        return $floorPrefix . str_pad($unitIndex, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Generate national ID.
     */
    private function generateNationalId(): string
    {
        return (string) rand(1000000000, 9999999999);
    }

    /**
     * Generate phone number.
     */
    private function generatePhoneNumber(): string
    {
        return '+9665' . rand(10000000, 99999999);
    }

    /**
     * Get random name.
     */
    private function getRandomName(): string
    {
        $firstNames = ['أحمد', 'محمد', 'علي', 'عمر', 'خالد', 'سعود', 'فهد', 'عبدالله', 'يوسف', 'حسان'];
        $lastNames = ['السعود', 'الرشيد', 'المنصوري', 'الزهراني', 'الغامدي', 'العتيبي', 'الشمراني', 'المطيري'];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    /**
     * Get random relation.
     */
    private function getRandomRelation(): string
    {
        $relations = ['Brother', 'Sister', 'Father', 'Mother', 'Spouse', 'Son', 'Daughter', 'Friend'];
        return $relations[array_rand($relations)];
    }

    /**
     * Generate contract number.
     */
    private function generateContractNumber(int $ownershipId, int $index): string
    {
        return 'CNT-' . str_pad($ownershipId, 3, '0', STR_PAD_LEFT) . '-' . date('Y') . '-' . str_pad($index, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Ejar code.
     */
    private function generateEjarCode(): string
    {
        return 'EJAR-' . strtoupper(Str::random(10));
    }

    /**
     * Generate invoice number.
     */
    private function generateInvoiceNumber(int $ownershipId, int $index): string
    {
        return 'INV-' . str_pad($ownershipId, 3, '0', STR_PAD_LEFT) . '-' . date('Y') . '-' . str_pad($index, 5, '0', STR_PAD_LEFT);
    }
}

