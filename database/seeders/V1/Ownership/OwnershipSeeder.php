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

        $this->command->info('Creating 20 ownerships with realistic data...');
        $this->command->info('');

        // Realistic ownership data
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
            [
                'name' => 'King Fahd Properties',
                'legal' => 'King Fahd Properties LLC',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7004567890',
                'tax_id' => '300456789000003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'info@kfproperties.com',
                'phone' => '+966112345681',
            ],
            [
                'name' => 'Olaya Real Estate Group',
                'legal' => 'Olaya Real Estate Group Co.',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7005678901',
                'tax_id' => '300567890100003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'contact@olayare.com',
                'phone' => '+966112345682',
            ],
            // Jeddah Ownerships
            [
                'name' => 'Al-Madinah Investment Group',
                'legal' => 'Al-Madinah Investment Group Co.',
                'type' => 'company',
                'ownership_type' => 'investment',
                'registration' => 'CR7006789012',
                'tax_id' => '300678901200003',
                'city' => 'Jeddah',
                'state' => 'Makkah Province',
                'email' => 'info@almadinah.com',
                'phone' => '+966122345678',
            ],
            [
                'name' => 'Red Sea Properties',
                'legal' => 'Red Sea Properties LLC',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7007890123',
                'tax_id' => '300789012300003',
                'city' => 'Jeddah',
                'state' => 'Makkah Province',
                'email' => 'info@redseaprop.com',
                'phone' => '+966122345679',
            ],
            [
                'name' => 'Corniche Real Estate',
                'legal' => 'Corniche Real Estate Co.',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7008901234',
                'tax_id' => '300890123400003',
                'city' => 'Jeddah',
                'state' => 'Makkah Province',
                'email' => 'contact@cornichere.com',
                'phone' => '+966122345680',
            ],
            // Dammam/Khobar Ownerships
            [
                'name' => 'Eastern Province Properties',
                'legal' => 'Eastern Province Properties LLC',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7009012345',
                'tax_id' => '300901234500003',
                'city' => 'Dammam',
                'state' => 'Eastern Province',
                'email' => 'info@epproperties.com',
                'phone' => '+966133456789',
            ],
            [
                'name' => 'Khobar Real Estate Group',
                'legal' => 'Khobar Real Estate Group Co.',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7000123456',
                'tax_id' => '300012345600003',
                'city' => 'Khobar',
                'state' => 'Eastern Province',
                'email' => 'info@khobarre.com',
                'phone' => '+966133456790',
            ],
            // More Riyadh Ownerships
            [
                'name' => 'Al-Wurud Investment',
                'legal' => 'Al-Wurud Investment Co.',
                'type' => 'company',
                'ownership_type' => 'investment',
                'registration' => 'CR7001234568',
                'tax_id' => '300123456800003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'info@alwurud.com',
                'phone' => '+966112345683',
            ],
            [
                'name' => 'Malqa Properties',
                'legal' => 'Malqa Properties LLC',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7002345679',
                'tax_id' => '300234567900003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'contact@malqaprop.com',
                'phone' => '+966112345684',
            ],
            [
                'name' => 'Sulaimaniyah Real Estate',
                'legal' => 'Sulaimaniyah Real Estate Co.',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7003456790',
                'tax_id' => '300345679000003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'info@sulaimaniyah.com',
                'phone' => '+966112345685',
            ],
            [
                'name' => 'Al-Nakheel Properties',
                'legal' => 'Al-Nakheel Properties LLC',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7004567901',
                'tax_id' => '300456790100003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'info@alnakheel.com',
                'phone' => '+966112345686',
            ],
            [
                'name' => 'King Abdullah Real Estate',
                'legal' => 'King Abdullah Real Estate Co.',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7005679012',
                'tax_id' => '300567901200003',
                'city' => 'Riyadh',
                'state' => 'Riyadh Province',
                'email' => 'contact@kare.com',
                'phone' => '+966112345687',
            ],
            // More Jeddah Ownerships
            [
                'name' => 'Al-Balad Properties',
                'legal' => 'Al-Balad Properties LLC',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7006789013',
                'tax_id' => '300678901300003',
                'city' => 'Jeddah',
                'state' => 'Makkah Province',
                'email' => 'info@albalad.com',
                'phone' => '+966122345681',
            ],
            [
                'name' => 'Al-Hamra Real Estate',
                'legal' => 'Al-Hamra Real Estate Co.',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7007890124',
                'tax_id' => '300789012400003',
                'city' => 'Jeddah',
                'state' => 'Makkah Province',
                'email' => 'contact@alhamra.com',
                'phone' => '+966122345682',
            ],
            // More Dammam/Khobar Ownerships
            [
                'name' => 'Al-Khobar Towers',
                'legal' => 'Al-Khobar Towers LLC',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7008901235',
                'tax_id' => '300890123500003',
                'city' => 'Khobar',
                'state' => 'Eastern Province',
                'email' => 'info@alkhobartowers.com',
                'phone' => '+966133456791',
            ],
            [
                'name' => 'Dammam Commercial Center',
                'legal' => 'Dammam Commercial Center Co.',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7009012346',
                'tax_id' => '300901234600003',
                'city' => 'Dammam',
                'state' => 'Eastern Province',
                'email' => 'info@dcc.com',
                'phone' => '+966133456792',
            ],
            [
                'name' => 'Al-Dhahran Properties',
                'legal' => 'Al-Dhahran Properties LLC',
                'type' => 'company',
                'ownership_type' => 'commercial',
                'registration' => 'CR7000123457',
                'tax_id' => '300012345700003',
                'city' => 'Dhahran',
                'state' => 'Eastern Province',
                'email' => 'contact@aldhahran.com',
                'phone' => '+966133456793',
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

                $this->command->info("✓ Created ownership " . ($index + 1) . "/20: {$ownership->name}");
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
