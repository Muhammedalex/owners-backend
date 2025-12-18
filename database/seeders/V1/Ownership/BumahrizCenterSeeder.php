<?php

namespace Database\Seeders\V1\Ownership;

use App\Models\V1\Auth\Role;
use App\Models\V1\Auth\User;
use App\Models\V1\Contract\Contract;
use App\Models\V1\Contract\ContractTerm;
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
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\OwnershipBoardMemberRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\UserOwnershipMappingRepositoryInterface;
use App\Services\V1\Ownership\OwnershipService;
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

            // Step 2: Create Ownership (مركز بامحرز)
            $ownership = $this->createOwnership($superAdmin);

            // Step 3: Create Users (1 Owner + 2 Tenants)
            $owner = $this->createOwner($ownership);
            $tenants = $this->createTenants($ownership);

            // Step 4: Create Property Structure (Portfolio, Building, Floors, Units)
            $this->createPropertyStructure($ownership);

            // Step 5: Create Tenant Records
            $this->createTenantRecords($ownership, $tenants);

            // Step 6: Create Contracts
            $this->createContracts($ownership, $tenants);

            // Step 7: Create Invoices and Payments
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
     * Create Owner user (salem@owners.com).
     */
    private function createOwner(Ownership $ownership): User
    {
        $userRepository = app(UserRepositoryInterface::class);
        $mappingRepository = app(UserOwnershipMappingRepositoryInterface::class);
        $boardMemberRepository = app(OwnershipBoardMemberRepositoryInterface::class);
        $ownerRole = Role::withSystemRoles()->where('name', 'Owner')->first();

        if (!$ownerRole) {
            $this->command->error('Owner role not found. Please run RoleSeeder first.');
            throw new \Exception('Owner role not found');
        }

        // Check if owner already exists
        $owner = $userRepository->findByEmail('salem@owners.com');
        if (!$owner) {
            $owner = $userRepository->create([
                'uuid' => '550e8400-e29b-41d4-a736-446655440010',
                'email' => 'salem@owners.com',
                'password' => 'password',
                'first' => 'سالم',
                'last' => 'بامحرز',
                'company' => 'مركز بامحرز',
                'phone' => '+966501234567',
                'type' => 'owner',
                'active' => true,
                'email_verified_at' => now(),
                'timezone' => 'Asia/Riyadh',
                'locale' => 'ar',
            ]);
            $owner->assignRole('Owner');
            $this->command->info('✓ Created owner: salem@owners.com');
        } else {
            if (!$owner->hasRole('Owner')) {
                $owner->assignRole('Owner');
            }
            $this->command->info('✓ Owner already exists: salem@owners.com');
        }

        // Map owner to ownership
        $mapping = $mappingRepository->findByUserAndOwnership($owner->id, $ownership->id);
        if (!$mapping) {
            $mappingRepository->create([
                'user_id' => $owner->id,
                'ownership_id' => $ownership->id,
                'default' => true,
            ]);
            $this->command->info('  → Mapped owner to ownership');
        }

        // Add as board member
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

        return $owner;
    }

    /**
     * Create Tenant users (2 tenants).
     */
    private function createTenants(Ownership $ownership): array
    {
        $userRepository = app(UserRepositoryInterface::class);
        $mappingRepository = app(UserOwnershipMappingRepositoryInterface::class);

        $tenantsData = [
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440020',
                'email' => 'tenant1@bumahriz.com',
                'first' => 'أحمد',
                'last' => 'المالكي',
                'phone' => '+966502345678',
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440021',
                'email' => 'tenant2@bumahriz.com',
                'first' => 'محمد',
                'last' => 'الغامدي',
                'phone' => '+966503456789',
            ],
        ];

        $tenants = [];
        foreach ($tenantsData as $index => $tenantData) {
            $tenant = $userRepository->findByEmail($tenantData['email']);
            if (!$tenant) {
                $tenant = $userRepository->create([
                    'uuid' => $tenantData['uuid'],
                    'email' => $tenantData['email'],
                    'password' => 'password',
                    'first' => $tenantData['first'],
                    'last' => $tenantData['last'],
                    'phone' => $tenantData['phone'],
                    'type' => 'tenant',
                    'active' => true,
                    'email_verified_at' => now(),
                    'timezone' => 'Asia/Riyadh',
                    'locale' => 'ar',
                ]);
                $this->command->info('✓ Created tenant user: ' . $tenantData['email']);
            } else {
                $this->command->info('✓ Tenant user already exists: ' . $tenantData['email']);
            }

            // Map tenant to ownership
            $mapping = $mappingRepository->findByUserAndOwnership($tenant->id, $ownership->id);
            if (!$mapping) {
                $mappingRepository->create([
                    'user_id' => $tenant->id,
                    'ownership_id' => $ownership->id,
                    'default' => false,
                ]);
            }

            $tenants[] = $tenant;
        }

        return $tenants;
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

        foreach ($tenantUsers as $index => $user) {
            $tenant = Tenant::where('user_id', $user->id)
                ->where('ownership_id', $ownership->id)
                ->first();

            if (!$tenant) {
                $tenant = Tenant::create([
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
            $startDate = now()->subMonths(rand(1, 6));
            $endDate = $startDate->copy()->addYears(rand(1, 2));
            $rent = $unit->price_monthly ?? rand(5000, 15000);
            $deposit = $rent * 3; // 3 months deposit

            $contract = Contract::where('unit_id', $unit->id)
                ->where('tenant_id', $tenant->id)
                ->first();

            if (!$contract) {
                $contract = Contract::create([
                    'uuid' => (string) Str::uuid(),
                    'unit_id' => $unit->id,
                    'tenant_id' => $tenant->id,
                    'ownership_id' => $ownership->id,
                    'number' => $this->generateContractNumber($ownership->id, $index + 1),
                    'version' => 1,
                    'parent_id' => null,
                    'ejar_code' => rand(0, 1) ? $this->generateEjarCode() : null, // 50% chance
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'rent' => $rent,
                    'payment_frequency' => 'monthly',
                    'deposit' => $deposit,
                    'deposit_status' => 'paid',
                    'document' => null,
                    'signature' => null,
                    'status' => 'active',
                    // 'notes' => 'عقد إيجار محل تجاري في مركز بامحرز',
                ]);

                // Update unit status
                $unit->update(['status' => 'rented']);

                // Create contract terms
                $this->createContractTerms($contract);

                $this->command->info("    ✓ Created contract: {$contract->number} for unit {$unit->number}");
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

        $invoiceCounter = 1;
        foreach ($contracts as $contract) {
            // Create 1-3 invoices per contract
            $invoicesCount = rand(1, 3);

            for ($i = 0; $i < $invoicesCount; $i++) {
                $periodStart = now()->subMonths($invoicesCount - $i)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();
                $due = $periodEnd->copy()->addDays(7);

                $invoiceNumber = $this->generateInvoiceNumber($ownership->id, $invoiceCounter++);

                $invoice = Invoice::where('contract_id', $contract->id)
                    ->where('number', $invoiceNumber)
                    ->first();

                if (!$invoice) {
                    $amount = $contract->rent;
                    $taxRate = 15.00;
                    $tax = $amount * ($taxRate / 100);
                    $total = $amount + $tax;

                    $invoice = Invoice::create([
                        'uuid' => (string) Str::uuid(),
                        'contract_id' => $contract->id,
                        'ownership_id' => $ownership->id,
                        'number' => $invoiceNumber,
                        'period_start' => $periodStart->format('Y-m-d'),
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'due' => $due->format('Y-m-d'),
                        'amount' => $amount,
                        'tax' => $tax,
                        'tax_rate' => $taxRate,
                        'total' => $total,
                        'status' => $due->isPast() && $i < $invoicesCount - 1 ? 'paid' : 'sent',
                        'notes' => 'فاتورة إيجار محل تجاري',
                        'generated_by' => $ownership->created_by,
                        'generated_at' => $periodStart,
                        'paid_at' => $due->isPast() && $i < $invoicesCount - 1 ? $due->copy()->addDays(rand(1, 5)) : null,
                    ]);

                    // Create invoice items
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'type' => 'rent',
                        'description' => 'إيجار محل تجاري - ' . $periodStart->format('Y-m'),
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'total' => $amount,
                    ]);

                    // Create payment if invoice is paid
                    if ($invoice->status === 'paid' && $invoice->paid_at) {
                        Payment::create([
                            'uuid' => (string) Str::uuid(),
                            'invoice_id' => $invoice->id,
                            'ownership_id' => $ownership->id,
                            'method' => rand(0, 1) ? 'cash' : 'bank_transfer',
                            'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
                            'amount' => $total,
                            'currency' => 'SAR',
                            'status' => 'paid',
                            'paid_at' => $invoice->paid_at,
                            'confirmed_by' => $ownership->created_by,
                            'notes' => 'دفعة إيجار محل تجاري',
                        ]);
                    }

                    $this->command->info("    ✓ Created invoice: {$invoice->number} (Status: {$invoice->status})");
                }
            }
        }

        $this->command->info('  ✓ Invoices and payments completed');
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

