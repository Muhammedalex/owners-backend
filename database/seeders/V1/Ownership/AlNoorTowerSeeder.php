<?php

namespace Database\Seeders\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Contract\Contract;
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

class AlNoorTowerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏢 Starting Al Noor Tower (برج النور) seeding...');
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
            $tenants = [];
            for ($i = 1; $i <= 5; $i++) {
                $tenants[] = $userRepository->findByEmail("tenant{$i}@owners.com");
            }

            // Step 4: Create Ownership (برج النور)
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
            $this->command->info('✅ Al Noor Tower seeding completed successfully!');
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
            $superAdminRole = \App\Models\V1\Auth\Role::withSystemRoles()->where('name', 'Super Admin')->first();
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
     * Create Ownership (برج النور).
     */
    private function createOwnership(User $superAdmin): Ownership
    {
        $ownershipService = app(OwnershipService::class);

        $ownershipData = [
            'uuid' => '550e8400-e29b-41d4-a716-446655440002',
            'name' => 'برج النور',
            'legal' => 'برج النور - حي الزهراء',
            'type' => 'company',
            'ownership_type' => 'residential',
            'registration' => 'CR7001234568',
            'tax_id' => '300123456700004',
            'street' => 'شارع الملك فهد',
            'city' => 'Riyadh',
            'state' => 'Riyadh Province',
            'country' => 'Saudi Arabia',
            'zip_code' => '11564',
            'email' => 'info@alnoor.com',
            'phone' => '+966112345678',
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
                'default' => false, // Not default (Bumahriz is default)
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
                'start_date' => now()->subYears(3),
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
            'name' => 'برج النور - حي الزهراء',
            'type' => 'residential',
            'description' => 'برج سكني فاخر يحتوي على شقق سكنية',
            'area' => 8000.00, // 8000 sqm
        ]);

        // Add portfolio location
        $location = PortfolioLocation::where('portfolio_id', $portfolio->id)->first();
        if (!$location) {
            PortfolioLocation::create([
                'portfolio_id' => $portfolio->id,
                'street' => 'شارع الملك فهد',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'country' => 'Saudi Arabia',
                'zip_code' => '11564',
                'latitude' => 24.7136, // Riyadh coordinates
                'longitude' => 46.6753,
                'primary' => true,
            ]);
        }

        // Get the default building (created by OwnershipService)
        $building = Building::where('portfolio_id', $portfolio->id)->first();
        if (!$building) {
            $this->command->error('  Building not found. This should not happen.');
            return;
        }

        // Update building details for residential tower
        $building->update([
            'name' => 'برج النور',
            'type' => 'residential',
            'description' => 'برج سكني فاخر يحتوي على 10 طوابق',
            'street' => 'شارع الملك فهد',
            'city' => 'Riyadh',
            'state' => 'Riyadh Province',
            'country' => 'Saudi Arabia',
            'zip_code' => '11564',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'floors' => 10,
            'year' => 2020,
        ]);

        $this->command->info('    ✓ Updated portfolio and building');

        // Create floors (Ground Floor + 9 Residential Floors)
        $floors = [];
        for ($f = 0; $f <= 9; $f++) {
            $floor = BuildingFloor::where('building_id', $building->id)
                ->where('number', $f)
                ->first();

            if (!$floor) {
                $floorName = $f === 0 ? 'الطابق الأرضي' : "الطابق {$f}";
                $floor = BuildingFloor::create([
                    'building_id' => $building->id,
                    'number' => $f,
                    'name' => $floorName,
                    'description' => $f === 0 ? 'الطابق الأرضي - مواقف وخدمات' : "الطابق {$f} - شقق سكنية",
                    'units' => $f === 0 ? 0 : 4, // Ground floor: no units, other floors: 4 apartments each
                    'active' => true,
                ]);
            }
            if ($f > 0) {
                $floors[] = $floor;
            }
            $this->command->info("    ✓ Created/Updated floor: {$floor->name}");
        }

        // Create units (apartments) for each floor
        $unitTypes = ['2BR', '3BR', '4BR', 'Penthouse'];
        $unitNumbers = [];
        $unitCounter = 1;

        foreach ($floors as $floor) {
            for ($u = 1; $u <= 4; $u++) {
                $unitNumber = str_pad($unitCounter, 3, '0', STR_PAD_LEFT);
                $unitType = $unitTypes[($u - 1) % 4];
                $unitName = "شقة {$unitNumber}";
                
                // Calculate area and prices based on unit type
                $area = $unitType === '2BR' ? 120.00 : ($unitType === '3BR' ? 150.00 : ($unitType === '4BR' ? 200.00 : 300.00));
                $baseRent = $unitType === '2BR' ? rand(40000, 50000) : ($unitType === '3BR' ? rand(50000, 65000) : ($unitType === '4BR' ? rand(65000, 80000) : rand(100000, 150000)));
                $monthly = round($baseRent / 12, 2);
                $quarterly = round($baseRent / 4, 2);
                $yearly = $baseRent;

                $unit = Unit::where('building_id', $building->id)
                    ->where('number', $unitNumber)
                    ->first();

                if (!$unit) {
                    $unit = Unit::create([
                        'uuid' => (string) Str::uuid(),
                        'ownership_id' => $ownership->id,
                        'building_id' => $building->id,
                        'floor_id' => $floor->id,
                        'number' => $unitNumber,
                        'name' => $unitName,
                        'type' => 'apartment',
                        'description' => "شقة سكنية في {$floor->name}",
                        'status' => 'available',
                        'area' => $area,
                        'price_monthly' => $monthly,
                        'price_quarterly' => $quarterly,
                        'price_yearly' => $yearly,
                        'active' => true,
                    ]);

                    // Create unit specifications
                    UnitSpecification::create([
                        'unit_id' => $unit->id,
                        'key' => 'unit_type',
                        'value' => $unitType,
                    ]);
                }
                $unitNumbers[] = $unitNumber;
                $unitCounter++;
            }
        }

        $this->command->info('    ✓ Created ' . count($unitNumbers) . ' units');
    }

    /**
     * Create Tenant Records.
     */
    private function createTenantRecords(Ownership $ownership, array $tenants): void
    {
        $this->command->info('  Creating tenant records...');
        $tenantService = app(TenantService::class);

        $tenantData = [
            [
                'national_id' => '1012345678',
                'id_type' => 'national_id',
                'rating' => 'excellent',
                'employment' => 'employed',
                'employer' => 'شركة أرامكو',
                'income' => 25000.00,
            ],
            [
                'national_id' => '1022345678',
                'id_type' => 'national_id',
                'rating' => 'good',
                'employment' => 'self_employed',
                'employer' => null,
                'income' => 18000.00,
            ],
            [
                'national_id' => '1032345678',
                'id_type' => 'national_id',
                'rating' => 'excellent',
                'employment' => 'employed',
                'employer' => 'وزارة التعليم',
                'income' => 22000.00,
            ],
            [
                'national_id' => '1042345678',
                'id_type' => 'national_id',
                'rating' => 'good',
                'employment' => 'employed',
                'employer' => 'البنك الأهلي',
                'income' => 20000.00,
            ],
            [
                'national_id' => '1052345678',
                'id_type' => 'national_id',
                'rating' => 'fair',
                'employment' => 'employed',
                'employer' => 'شركة الاتصالات',
                'income' => 15000.00,
            ],
        ];

        $tenantRecords = [];
        foreach ($tenants as $index => $tenant) {
            if (!$tenant) {
                continue;
            }

            $existingTenant = Tenant::where('user_id', $tenant->id)
                ->where('ownership_id', $ownership->id)
                ->first();

            if (!$existingTenant) {
                $data = array_merge($tenantData[$index], [
                    'user_id' => $tenant->id,
                    'ownership_id' => $ownership->id,
                ]);

                $tenantRecord = $tenantService->create($data);
                $tenantRecords[] = $tenantRecord;
                $this->command->info("    ✓ Created tenant record for: {$tenant->email}");
            } else {
                $tenantRecords[] = $existingTenant;
                $this->command->info("    ✓ Tenant record already exists for: {$tenant->email}");
            }
        }

        $this->command->info('  → Created ' . count($tenantRecords) . ' tenant records');
    }

    /**
     * Create Contracts.
     */
    private function createContracts(Ownership $ownership, array $tenants): void
    {
        $this->command->info('  Creating contracts...');
        $contractService = app(ContractService::class);

        // Get available units
        $units = Unit::whereHas('building.portfolio', function ($q) use ($ownership) {
            $q->where('ownership_id', $ownership->id);
        })
        ->where('status', 'available')
        ->limit(5)
        ->get();

        if ($units->isEmpty()) {
            $this->command->error('  No available units found. Cannot create contracts.');
            return;
        }

        $contracts = [];
        $startDate = Carbon::parse('2025-01-01');
        $paymentFrequencies = ['monthly', 'quarterly', 'yearly'];
        $baseRents = [60000, 75000, 90000, 120000, 150000]; // Annual rent

        foreach ($tenants as $index => $tenant) {
            if (!$tenant || $index >= count($units)) {
                continue;
            }

            $unit = $units[$index];
            $tenantRecord = Tenant::where('user_id', $tenant->id)
                ->where('ownership_id', $ownership->id)
                ->first();

            if (!$tenantRecord) {
                continue;
            }

            $endDate = $startDate->copy()->addYear();
            $paymentFrequency = $paymentFrequencies[$index % 3];
            $baseRent = $baseRents[$index % 5];

            $contractData = [
                'ownership_id' => $ownership->id,
                'tenant_id' => $tenantRecord->id,
                'unit_ids' => [$unit->id],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'base_rent' => $baseRent,
                'payment_frequency' => $paymentFrequency,
                'status' => 'active',
                'ejar_code' => 'EJ' . str_pad($ownership->id, 4, '0', STR_PAD_LEFT) . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
            ];

            try {
                $contract = $contractService->create($contractData);
                $contracts[] = $contract;
                $this->command->info("    ✓ Created contract for tenant: {$tenant->email}");
            } catch (\Exception $e) {
                $this->command->error("    ✗ Failed to create contract for tenant {$tenant->email}: " . $e->getMessage());
            }
        }

        $this->command->info('  → Created ' . count($contracts) . ' contracts');
    }

    /**
     * Create Invoices and Payments.
     */
    private function createInvoicesAndPayments(Ownership $ownership): void
    {
        $this->command->info('  Creating invoices and payments...');
        $contractInvoiceService = app(ContractInvoiceService::class);

        // Get active contracts
        $contracts = Contract::where('ownership_id', $ownership->id)
            ->where('status', 'active')
            ->get();

        if ($contracts->isEmpty()) {
            $this->command->warn('  No active contracts found. Skipping invoice generation.');
            return;
        }

        $totalInvoices = 0;
        $totalPayments = 0;

        foreach ($contracts as $contract) {
            // Generate invoices for 2025 (Jan to Dec)
            $startDate = Carbon::parse('2025-01-01');
            $endDate = Carbon::parse('2025-12-31');

            $currentDate = $startDate->copy();
            $periodCounter = 1;

            while ($currentDate->lte($endDate) && $currentDate->lte($contract->end_date)) {
                $periodEnd = $this->getPeriodEnd($currentDate, $contract->payment_frequency);
                if ($periodEnd->gt($contract->end_date)) {
                    $periodEnd = Carbon::parse($contract->end_date);
                }
                if ($periodEnd->gt($endDate)) {
                    $periodEnd = $endDate;
                }

                // Determine invoice status based on date and random chance
                $invoiceStatus = $this->determineInvoiceStatus($currentDate, $periodEnd);

                try {
                    $invoice = $contractInvoiceService->generateFromContract($contract, [
                        'period_start' => $currentDate->format('Y-m-d'),
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'status' => $invoiceStatus,
                    ]);

                    // Create invoice items
                    $this->createInvoiceItemsForContract($invoice, $contract);

                    // Create payments based on status
                    if (in_array($invoiceStatus, ['paid', 'partial'])) {
                        $this->createPaymentsForInvoice($invoice, $invoiceStatus);
                        $totalPayments++;
                    }

                    $totalInvoices++;
                } catch (\Exception $e) {
                    $this->command->error("    ✗ Failed to create invoice: " . $e->getMessage());
                }

                $currentDate = $periodEnd->copy()->addDay();
                $periodCounter++;
            }
        }

        $this->command->info("  → Created {$totalInvoices} invoices and {$totalPayments} payments");
    }

    /**
     * Get period end date based on payment frequency.
     */
    private function getPeriodEnd(Carbon $startDate, string $frequency): Carbon
    {
        return match ($frequency) {
            'weekly' => $startDate->copy()->addWeek(),
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'semi_annually' => $startDate->copy()->addMonths(6),
            'yearly' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };
    }

    /**
     * Determine invoice status based on date and random chance.
     */
    private function determineInvoiceStatus(Carbon $periodStart, Carbon $periodEnd): string
    {
        $now = Carbon::now();
        $dueDate = $periodEnd->copy()->addDays(10); // 10 days after period end

        if ($now->gt($dueDate)) {
            // Overdue
            return rand(0, 100) < 30 ? 'overdue' : 'paid'; // 30% chance overdue
        } elseif ($now->gte($periodStart) && $now->lte($dueDate)) {
            // Current period
            $rand = rand(0, 100);
            if ($rand < 40) return 'paid';
            if ($rand < 70) return 'sent';
            if ($rand < 85) return 'viewed';
            return 'partial';
        } else {
            // Future period
            return rand(0, 100) < 20 ? 'sent' : 'draft';
        }
    }

    /**
     * Create invoice items for contract.
     */
    private function createInvoiceItemsForContract(Invoice $invoice, Contract $contract): void
    {
        $monthlyRent = $contract->total_rent / 12;
        $periodMonths = Carbon::parse($invoice->period_start)->diffInMonths(Carbon::parse($invoice->period_end)) + 1;
        $amount = $monthlyRent * $periodMonths;

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'rent',
            'description' => "إيجار شهري - {$contract->payment_frequency}",
            'quantity' => $periodMonths,
            'unit_price' => $monthlyRent,
            'total' => $amount,
        ]);
    }

    /**
     * Create payments for invoice.
     */
    private function createPaymentsForInvoice(Invoice $invoice, string $status): void
    {
        $methods = ['cash', 'bank_transfer', 'check'];
        $method = $methods[array_rand($methods)];

        if ($status === 'paid') {
            // Full payment
            Payment::create([
                'invoice_id' => $invoice->id,
                'ownership_id' => $invoice->ownership_id,
                'method' => $method,
                'amount' => $invoice->total,
                'currency' => 'SAR',
                'status' => 'paid',
                'paid_at' => Carbon::parse($invoice->period_end)->addDays(rand(1, 10)),
                'transaction_id' => 'TXN' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT),
            ]);
        } elseif ($status === 'partial') {
            // Partial payment (60-90% of total)
            $partialAmount = $invoice->total * (rand(60, 90) / 100);
            Payment::create([
                'invoice_id' => $invoice->id,
                'ownership_id' => $invoice->ownership_id,
                'method' => $method,
                'amount' => $partialAmount,
                'currency' => 'SAR',
                'status' => 'paid',
                'paid_at' => Carbon::parse($invoice->period_end)->addDays(rand(1, 15)),
                'transaction_id' => 'TXN' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT),
            ]);
        }
    }
}

