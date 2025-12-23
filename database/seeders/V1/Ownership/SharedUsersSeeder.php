<?php

namespace Database\Seeders\V1\Ownership;

use App\Models\V1\Auth\Role;
use App\Models\V1\Auth\User;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\UserOwnershipMappingRepositoryInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Shared Users Seeder
 * Creates users that will be shared across multiple ownerships
 */
class SharedUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👥 Starting Shared Users seeding...');
        $this->command->info('');

        DB::transaction(function () {
            $userRepository = app(UserRepositoryInterface::class);
            $mappingRepository = app(UserOwnershipMappingRepositoryInterface::class);

            // Get all roles
            $roles = [
                'Owner' => Role::withSystemRoles()->where('name', 'Owner')->first(),
                'Board Member' => Role::withSystemRoles()->where('name', 'Board Member')->first(),
                'Moderator' => Role::withSystemRoles()->where('name', 'Moderator')->first(),
                'Accountant' => Role::withSystemRoles()->where('name', 'Accountant')->first(),
                'Property Manager' => Role::withSystemRoles()->where('name', 'Property Manager')->first(),
                'Maintenance Manager' => Role::withSystemRoles()->where('name', 'Maintenance Manager')->first(),
                'Facility Manager' => Role::withSystemRoles()->where('name', 'Facility Manager')->first(),
                'Collector' => Role::withSystemRoles()->where('name', 'Collector')->first(),
            ];

            // Validate all roles exist
            foreach ($roles as $roleName => $role) {
                if (!$role) {
                    $this->command->error("Role '{$roleName}' not found. Please run RoleSeeder first.");
                    throw new \Exception("Role '{$roleName}' not found");
                }
            }

            // ============================================
            // 1. OWNER (salem@owners.com)
            // ============================================
            $owner = $this->createOrGetUser($userRepository, [
                'uuid' => '550e8400-e29b-41d4-a736-446655440010',
                'email' => 'salem@owners.com',
                'password' => 'password',
                'first' => 'سالم',
                'last' => 'بامحرز',
                'company' => 'Owners Management',
                'phone' => '+966501234567',
                'type' => 'owner',
                'active' => true,
                'email_verified_at' => now(),
                'timezone' => 'Asia/Riyadh',
                'locale' => 'ar',
            ], 'Owner', $roles['Owner']);

            // ============================================
            // 2. BOARD MEMBER
            // ============================================
            $boardMember = $this->createOrGetUser($userRepository, [
                'uuid' => '550e8400-e29b-41d4-a736-446655440020',
                'email' => 'board.member@owners.com',
                'password' => 'password',
                'first' => 'عبدالله',
                'last' => 'العتيبي',
                'company' => 'Owners Management',
                'phone' => '+966502345678',
                'type' => 'board_member',
                'active' => true,
                'email_verified_at' => now(),
                'timezone' => 'Asia/Riyadh',
                'locale' => 'ar',
            ], 'Board Member', $roles['Board Member']);

            // ============================================
            // 3. MODERATOR
            // ============================================
            $moderator = $this->createOrGetUser($userRepository, [
                'uuid' => '550e8400-e29b-41d4-a736-446655440030',
                'email' => 'moderator@owners.com',
                'password' => 'password',
                'first' => 'خالد',
                'last' => 'الزهراني',
                'company' => 'Owners Management',
                'phone' => '+966503456789',
                'type' => 'moderator',
                'active' => true,
                'email_verified_at' => now(),
                'timezone' => 'Asia/Riyadh',
                'locale' => 'ar',
            ], 'Moderator', $roles['Moderator']);

            // ============================================
            // 4. ACCOUNTANT
            // ============================================
            $accountant = $this->createOrGetUser($userRepository, [
                'uuid' => '550e8400-e29b-41d4-a736-446655440040',
                'email' => 'accountant@owners.com',
                'password' => 'password',
                'first' => 'فهد',
                'last' => 'القحطاني',
                'company' => 'Owners Management',
                'phone' => '+966504567890',
                'type' => 'accountant',
                'active' => true,
                'email_verified_at' => now(),
                'timezone' => 'Asia/Riyadh',
                'locale' => 'ar',
            ], 'Accountant', $roles['Accountant']);

            // ============================================
            // 5. PROPERTY MANAGER
            // ============================================
            $propertyManager = $this->createOrGetUser($userRepository, [
                'uuid' => '550e8400-e29b-41d4-a736-446655440050',
                'email' => 'property.manager@owners.com',
                'password' => 'password',
                'first' => 'نواف',
                'last' => 'الدوسري',
                'company' => 'Owners Management',
                'phone' => '+966505678901',
                'type' => 'property_manager',
                'active' => true,
                'email_verified_at' => now(),
                'timezone' => 'Asia/Riyadh',
                'locale' => 'ar',
            ], 'Property Manager', $roles['Property Manager']);

            // ============================================
            // 6. MAINTENANCE MANAGER
            // ============================================
            $maintenanceManager = $this->createOrGetUser($userRepository, [
                'uuid' => '550e8400-e29b-41d4-a736-446655440060',
                'email' => 'maintenance.manager@owners.com',
                'password' => 'password',
                'first' => 'يوسف',
                'last' => 'الخالدي',
                'company' => 'Owners Management',
                'phone' => '+966506789012',
                'type' => 'maintenance_manager',
                'active' => true,
                'email_verified_at' => now(),
                'timezone' => 'Asia/Riyadh',
                'locale' => 'ar',
            ], 'Maintenance Manager', $roles['Maintenance Manager']);

            // ============================================
            // 7. FACILITY MANAGER
            // ============================================
            $facilityManager = $this->createOrGetUser($userRepository, [
                'uuid' => '550e8400-e29b-41d4-a736-446655440070',
                'email' => 'facility.manager@owners.com',
                'password' => 'password',
                'first' => 'عمر',
                'last' => 'الحربي',
                'company' => 'Owners Management',
                'phone' => '+966507890123',
                'type' => 'facility_manager',
                'active' => true,
                'email_verified_at' => now(),
                'timezone' => 'Asia/Riyadh',
                'locale' => 'ar',
            ], 'Facility Manager', $roles['Facility Manager']);

            // ============================================
            // 8. TENANTS (5 tenants)
            // ============================================
            $tenantsData = [
                [
                    'uuid' => '550e8400-e29b-41d4-a716-446655440100',
                    'email' => 'tenant1@owners.com',
                    'first' => 'أحمد',
                    'last' => 'المالكي',
                    'phone' => '+966508901234',
                ],
                [
                    'uuid' => '550e8400-e29b-41d4-a716-446655440101',
                    'email' => 'tenant2@owners.com',
                    'first' => 'محمد',
                    'last' => 'الغامدي',
                    'phone' => '+966509012345',
                ],
                [
                    'uuid' => '550e8400-e29b-41d4-a716-446655440102',
                    'email' => 'tenant3@owners.com',
                    'first' => 'علي',
                    'last' => 'السلمي',
                    'phone' => '+966500123456',
                ],
                [
                    'uuid' => '550e8400-e29b-41d4-a716-446655440103',
                    'email' => 'tenant4@owners.com',
                    'first' => 'حسن',
                    'last' => 'القرني',
                    'phone' => '+966510123456',
                ],
                [
                    'uuid' => '550e8400-e29b-41d4-a716-446655440104',
                    'email' => 'tenant5@owners.com',
                    'first' => 'عبدالرحمن',
                    'last' => 'الشهري',
                    'phone' => '+966511234567',
                ],
            ];

            $tenants = [];
            foreach ($tenantsData as $tenantData) {
                $tenant = $this->createOrGetUser($userRepository, [
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
                ], 'Tenant', Role::withSystemRoles()->where('name', 'Tenant')->first());
                $tenants[] = $tenant;
            }

            // ============================================
            // 9. COLLECTORS (3 collectors)
            // ============================================
            $collectorsData = [
                [
                    'uuid' => '550e8400-e29b-41d4-a736-446655440200',
                    'email' => 'collector1@owners.com',
                    'first' => 'مشعل',
                    'last' => 'العنزي',
                    'phone' => '+966512345678',
                ],
                [
                    'uuid' => '550e8400-e29b-41d4-a736-446655440201',
                    'email' => 'collector2@owners.com',
                    'first' => 'سلطان',
                    'last' => 'المطيري',
                    'phone' => '+966513456789',
                ],
                [
                    'uuid' => '550e8400-e29b-41d4-a736-446655440202',
                    'email' => 'collector3@owners.com',
                    'first' => 'راشد',
                    'last' => 'الرشيد',
                    'phone' => '+966514567890',
                ],
            ];

            $collectors = [];
            foreach ($collectorsData as $collectorData) {
                $collector = $this->createOrGetUser($userRepository, [
                    'uuid' => $collectorData['uuid'],
                    'email' => $collectorData['email'],
                    'password' => 'password',
                    'first' => $collectorData['first'],
                    'last' => $collectorData['last'],
                    'phone' => $collectorData['phone'],
                    'type' => 'collector',
                    'active' => true,
                    'email_verified_at' => now(),
                    'timezone' => 'Asia/Riyadh',
                    'locale' => 'ar',
                ], 'Collector', $roles['Collector']);
                $collectors[] = $collector;
            }

            $this->command->info('');
            $this->command->info('✅ Shared Users seeding completed successfully!');
            $this->command->info('');
            $this->command->info('Created users:');
            $this->command->info('  - 1 Owner');
            $this->command->info('  - 1 Board Member');
            $this->command->info('  - 1 Moderator');
            $this->command->info('  - 1 Accountant');
            $this->command->info('  - 1 Property Manager');
            $this->command->info('  - 1 Maintenance Manager');
            $this->command->info('  - 1 Facility Manager');
            $this->command->info('  - 5 Tenants');
            $this->command->info('  - 3 Collectors');
        });
    }

    /**
     * Create or get user by email.
     */
    private function createOrGetUser(
        UserRepositoryInterface $userRepository,
        array $userData,
        string $roleName,
        ?Role $role
    ): User {
        $user = $userRepository->findByEmail($userData['email']);
        
        if (!$user) {
            $user = $userRepository->create($userData);
            $this->command->info("✓ Created {$roleName}: {$userData['email']}");
        } else {
            $this->command->info("✓ {$roleName} already exists: {$userData['email']}");
        }

        // Assign role if not already assigned
        if ($role && !$user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }

        return $user;
    }

    /**
     * Map user to ownership.
     */
    public function mapUserToOwnership(User $user, int $ownershipId, bool $isDefault = false): void
    {
        $mappingRepository = app(UserOwnershipMappingRepositoryInterface::class);
        
        $mapping = $mappingRepository->findByUserAndOwnership($user->id, $ownershipId);
        if (!$mapping) {
            $mappingRepository->create([
                'user_id' => $user->id,
                'ownership_id' => $ownershipId,
                'default' => $isDefault,
            ]);
        }
    }
}

