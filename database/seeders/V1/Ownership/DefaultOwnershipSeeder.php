<?php

namespace Database\Seeders\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Services\V1\Ownership\OwnershipService;
use Illuminate\Database\Seeder;

class DefaultOwnershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏢 Creating default ownership...');
        $this->command->info('');

        // Get Super Admin user
        $superAdmin = User::role('Super Admin')->first();

        if (!$superAdmin) {
            $this->command->error('Super Admin user not found. Please run UserSeeder first.');
            return;
        }

        // Check if default ownership already exists
        $defaultOwnership = Ownership::where('uuid', '550e8400-e29b-41d4-a716-446655440001')->first();

        if ($defaultOwnership) {
            $this->command->info('✓ Default ownership already exists: ' . $defaultOwnership->name);
            
            // Ensure Super Admin is linked to it
            $this->ensureSuperAdminLinked($superAdmin, $defaultOwnership);
            return;
        }

        // Create default ownership
        $ownershipService = app(OwnershipService::class);

        $ownershipData = [
            'uuid' => '550e8400-e29b-41d4-a716-446655440001',
            'name' => 'الملكية الافتراضية',
            'legal' => 'الملكية الافتراضية - نظام إدارة الملكيات',
            'type' => 'company',
            'ownership_type' => 'commercial',
            'registration' => 'DEFAULT001',
            'tax_id' => null,
            'street' => 'شارع الملك فهد',
            'city' => 'Riyadh',
            'state' => 'Riyadh Province',
            'country' => 'Saudi Arabia',
            'zip_code' => '11564',
            'email' => 'info@owners.com',
            'phone' => '+966112345678',
            'active' => true,
            'created_by' => $superAdmin->id,
        ];

        $ownership = $ownershipService->create($ownershipData);
        
        // Note: OwnershipService::create() already links Super Admin users automatically
        // But we'll ensure it's set as default for Super Admin
        $this->ensureSuperAdminLinked($superAdmin, $ownership);

        $this->command->info('✓ Default ownership created: ' . $ownership->name);
        $this->command->info('✓ Super Admin linked to default ownership');
    }

    /**
     * Ensure Super Admin is linked to ownership and set as default if it's their first.
     */
    private function ensureSuperAdminLinked(User $superAdmin, Ownership $ownership): void
    {
        $mappingService = app(\App\Services\V1\Ownership\UserOwnershipMappingService::class);

        // Check if mapping exists
        $existingMapping = $mappingService->findByUserAndOwnership($superAdmin->id, $ownership->id);

        if (!$existingMapping) {
            // Link Super Admin to ownership
            $isFirstOwnership = $superAdmin->ownerships()->count() === 0;
            try {
                $mappingService->create([
                    'user_id' => $superAdmin->id,
                    'ownership_id' => $ownership->id,
                    'default' => $isFirstOwnership,
                ]);
                $this->command->info('✓ Super Admin linked to ownership (default: ' . ($isFirstOwnership ? 'yes' : 'no') . ')');
            } catch (\Exception $e) {
                // Mapping might already exist, ignore
            }
        } else {
            // If Super Admin has no default ownership, set this one as default
            $defaultMapping = $mappingService->getDefaultForUser($superAdmin->id);
            if (!$defaultMapping && !$existingMapping->default) {
                $mappingService->setAsDefault($existingMapping);
                $this->command->info('✓ Set ownership as default for Super Admin');
            }
        }
    }
}

