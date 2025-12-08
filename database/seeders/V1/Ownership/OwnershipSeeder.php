<?php

namespace Database\Seeders\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Repositories\V1\Ownership\Interfaces\OwnershipBoardMemberRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\UserOwnershipMappingRepositoryInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OwnershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ownershipRepository = app(OwnershipRepositoryInterface::class);
        $mappingRepository = app(UserOwnershipMappingRepositoryInterface::class);
        $boardMemberRepository = app(OwnershipBoardMemberRepositoryInterface::class);

        // Get Super Admin user (who will create ownerships)
        $superAdmin = User::where('email', 'admin@owners.com')->first();
        if (!$superAdmin) {
            $this->command->error('Super Admin user not found. Please run UserSeeder first.');
            return;
        }

        // Get Owner role users
        $ownerUsers = User::role('Owner')->get();
        if ($ownerUsers->isEmpty()) {
            $this->command->warn('No Owner users found. Creating ownerships without owners...');
        }

        // Get all staff users for mapping
        $staffUsers = User::whereIn('type', ['manager', 'accountant', 'maintenance', 'staff', 'legal', 'marketing', 'it', 'assistant'])
            ->get();

        $this->command->info('Creating 3 ownerships with realistic data...');
        $this->command->info('');

        // Realistic ownership data (only first 3)
        $ownershipsData = [
            // Riyadh Ownerships
            [
                'name' => 'Al-Rashid Real Estate Company',
                'legal' => 'Al-Rashid Real Estate Company Limited',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7001234567',
                'tax_id' => '300123456700003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'info@alrashid.com',
                'phone' => '+966112345678',
            ],
            [
                'name' => 'Al-Noor Property Management',
                'legal' => 'Al-Noor Property Management LLC',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7002345678',
                'tax_id' => '300234567800003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'contact@alnoor.com',
                'phone' => '+966112345679',
            ],
            [
                'name' => 'Saudi Real Estate Investment',
                'legal' => 'Saudi Real Estate Investment Co.',
                'type' => 'company',
                'ownership_type' => 'investment',
                'registration' => 'CR7003456789',
                'tax_id' => '300345678900003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'info@srei.com',
                'phone' => '+966112345680',
            ],
        ];

        $createdOwnerships = [];
        $ownerIndex = 0;

        foreach ($ownershipsData as $index => $data) {
            $uuid = '550e8400-e29b-41d4-a716-' . str_pad($index + 1, 12, '0', STR_PAD_LEFT);

            $ownership = $ownershipRepository->findByUuid($uuid);
            if (!$ownership) {
                $ownership = $ownershipRepository->create([
                    'uuid' => $uuid,
                    'name' => $data['name'],
                    'legal' => $data['legal'],
                    'type' => $data['type'],
                    'ownership_type' => $data['ownership_type'],
                    'registration' => $data['registration'],
                    'tax_id' => $data['tax_id'],
                    'street' => $this->getRandomStreet(),
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'country' => 'Saudi Arabia',
                    'zip_code' => $this->generateZipCode(),
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'active' => true,
                    'created_by' => $superAdmin->id,
                ]);

                $this->command->info("✓ Created ownership " . ($index + 1) . "/3: {$ownership->name}");
            } else {
                $this->command->info("✓ Ownership already exists: {$ownership->name}");
            }

            $createdOwnerships[] = $ownership;

            // Map owners to ownerships (distribute owners across ownerships)
            if ($ownerUsers->isNotEmpty() && $index < 3) {
                $owner = $ownerUsers[$ownerIndex % $ownerUsers->count()];
                $mapping = $mappingRepository->findByUserAndOwnership($owner->id, $ownership->id);
                if (!$mapping) {
                    $mappingRepository->create([
                        'user_id' => $owner->id,
                        'ownership_id' => $ownership->id,
                        'default' => $ownerIndex < $ownerUsers->count(), // First ownership for each owner is default
                    ]);
                    $this->command->info("  → Mapped {$owner->email} to {$ownership->name}");
                }

                // Add as board member
                $boardMember = $boardMemberRepository->findByOwnershipAndUser($ownership->id, $owner->id);
                if (!$boardMember) {
                    $roles = ['Chairman', 'CEO', 'Managing Director'];
                    $boardMemberRepository->create([
                        'ownership_id' => $ownership->id,
                        'user_id' => $owner->id,
                        'role' => $roles[$ownerIndex % count($roles)],
                        'active' => true,
                        'start_date' => now()->subYears(rand(1, 5)),
                    ]);
                }

                $ownerIndex++;
            }

            // Map some staff users to ownerships (distribute staff across ownerships)
            if ($staffUsers->isNotEmpty()) {
                $staffPerOwnership = max(1, (int) ($staffUsers->count() / count($ownershipsData)));
                $startIndex = ($index * $staffPerOwnership) % $staffUsers->count();

                for ($i = 0; $i < $staffPerOwnership && $i < $staffUsers->count(); $i++) {
                    $staff = $staffUsers[($startIndex + $i) % $staffUsers->count()];
                    $mapping = $mappingRepository->findByUserAndOwnership($staff->id, $ownership->id);
                    if (!$mapping) {
                        $mappingRepository->create([
                            'user_id' => $staff->id,
                            'ownership_id' => $ownership->id,
                            'default' => false,
                        ]);
                    }
                }
            }
        }

        $this->command->info('');
        $this->command->info('✅ Created ' . count($createdOwnerships) . ' ownerships successfully!');
        $this->command->info('');
        $this->command->info('Ownerships Summary:');
        $this->command->info('- Riyadh: ' . collect($createdOwnerships)->where('city', 'Riyadh')->count());
        $this->command->info('- Jeddah: ' . collect($createdOwnerships)->where('city', 'Jeddah')->count());
        $this->command->info('- Dammam/Khobar/Dhahran: ' . collect($createdOwnerships)->whereIn('city', ['Dammam', 'Khobar', 'Dhahran'])->count());
    }

    /**
     * Get random street
     */
    private function getRandomStreet(): string
    {
        $streets = [
            'King Fahd Road',
            'Prince Sultan Road',
            'King Abdullah Road',
            'King Khalid Road',
            'Olaya Street',
            'Tahlia Street',
            'Corniche Road',
            'Airport Road',
            'University Road',
            'Industrial Road',
        ];

        return $streets[array_rand($streets)] . ', ' . rand(1, 999);
    }

    /**
     * Generate zip code
     */
    private function generateZipCode(): string
    {
        return (string) rand(10000, 99999);
    }
}
