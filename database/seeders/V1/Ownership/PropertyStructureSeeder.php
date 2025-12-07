<?php

namespace Database\Seeders\V1\Ownership;

use App\Models\V1\Ownership\Building;
use App\Models\V1\Ownership\BuildingFloor;
use App\Models\V1\Ownership\Portfolio;
use App\Models\V1\Ownership\PortfolioLocation;
use App\Models\V1\Ownership\Unit;
use App\Models\V1\Ownership\UnitSpecification;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PropertyStructureSeeder extends Seeder
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

        $this->command->info('Creating property structure for ' . $ownerships->count() . ' ownerships...');
        $this->command->info('');

        foreach ($ownerships as $ownership) {
            $this->command->info("Processing ownership: {$ownership->name}");

            // Create 2-5 portfolios per ownership
            $portfoliosCount = rand(2, 5);
            $portfolios = [];

            for ($p = 1; $p <= $portfoliosCount; $p++) {
                $portfolio = Portfolio::create([
                    'uuid' => (string) Str::uuid(),
                    'ownership_id' => $ownership->id,
                    'parent_id' => null, // Root portfolios for now
                    'name' => $this->getPortfolioName($ownership->name, $p),
                    'code' => $this->generatePortfolioCode($ownership->id, $p),
                    'type' => $this->getRandomPortfolioType(),
                    'description' => "Portfolio {$p} for {$ownership->name}",
                    'area' => rand(5000, 50000) + (rand(0, 99) / 100), // 5000-50000 sqm
                    'active' => true,
                ]);

                // Create 1-2 locations per portfolio
                $locationsCount = rand(1, 2);
                for ($l = 1; $l <= $locationsCount; $l++) {
                    PortfolioLocation::create([
                        'portfolio_id' => $portfolio->id,
                        'street' => $this->getRandomStreet(),
                        'city' => $ownership->city,
                        'state' => $ownership->state,
                        'country' => $ownership->country ?? 'Saudi Arabia',
                        'zip_code' => $this->generateZipCode(),
                        'latitude' => $this->getLatitudeForCity($ownership->city),
                        'longitude' => $this->getLongitudeForCity($ownership->city),
                        'primary' => $l === 1, // First location is primary
                    ]);
                }

                $portfolios[] = $portfolio;
                $this->command->info("  ✓ Created portfolio: {$portfolio->name} ({$portfolio->code})");

                // Create 3-8 buildings per portfolio
                $buildingsCount = rand(3, 8);
                $buildings = [];

                for ($b = 1; $b <= $buildingsCount; $b++) {
                    $building = Building::create([
                        'uuid' => (string) Str::uuid(),
                        'portfolio_id' => $portfolio->id,
                        'ownership_id' => $ownership->id,
                        'parent_id' => null,
                        'name' => $this->getBuildingName($b),
                        'code' => $this->generateBuildingCode($portfolio->id, $b),
                        'type' => $this->getRandomBuildingType(),
                        'description' => "Building {$b} in {$portfolio->name}",
                        'street' => $this->getRandomStreet(),
                        'city' => $ownership->city,
                        'state' => $ownership->state,
                        'country' => $ownership->country ?? 'Saudi Arabia',
                        'zip_code' => $this->generateZipCode(),
                        'latitude' => $this->getLatitudeForCity($ownership->city),
                        'longitude' => $this->getLongitudeForCity($ownership->city),
                        'floors' => $floorsCount = rand(3, 15),
                        'year' => rand(2010, 2024),
                        'active' => true,
                    ]);

                    $buildings[] = $building;
                    $this->command->info("    ✓ Created building: {$building->name} ({$building->code}) - {$floorsCount} floors");

                    // Create floors for building
                    $floors = [];
                    for ($f = 1; $f <= $floorsCount; $f++) {
                        // Create some basements (negative numbers)
                        $floorNumber = $f;
                        if ($f <= 2 && rand(0, 1)) {
                            $floorNumber = -($f); // Basement floors
                        }

                        $floor = BuildingFloor::create([
                            'building_id' => $building->id,
                            'number' => $floorNumber,
                            'name' => $this->getFloorName($floorNumber),
                            'description' => "Floor {$floorNumber} of {$building->name}",
                            'units' => rand(5, 20),
                            'active' => true,
                        ]);

                        $floors[] = $floor;

                        // Create 5-20 units per floor
                        $unitsCount = rand(5, 20);
                        for ($u = 1; $u <= $unitsCount; $u++) {
                            $unitNumber = $this->generateUnitNumber($floorNumber, $u);
                            $unitType = $this->getRandomUnitType();
                            $area = $this->getAreaForUnitType($unitType);
                            $prices = $this->getPricesForUnitType($unitType, $area);

                            $unit = Unit::create([
                                'uuid' => (string) Str::uuid(),
                                'building_id' => $building->id,
                                'floor_id' => $floor->id,
                                'ownership_id' => $ownership->id,
                                'number' => $unitNumber,
                                'type' => $unitType,
                                'name' => "Unit {$unitNumber}",
                                'description' => "{$unitType} unit {$unitNumber} on floor {$floorNumber}",
                                'area' => $area,
                                'price_monthly' => $prices['monthly'],
                                'price_quarterly' => $prices['quarterly'],
                                'price_yearly' => $prices['yearly'],
                                'status' => $this->getRandomUnitStatus(),
                                'active' => true,
                            ]);

                            // Create specifications for unit
                            $this->createUnitSpecifications($unit, $unitType);
                        }
                    }
                }
            }

            $this->command->info("  ✓ Completed structure for {$ownership->name}");
            $this->command->info('');
        }

        $this->command->info('✅ Property structure seeded successfully!');
    }

    /**
     * Get portfolio name
     */
    private function getPortfolioName(string $ownershipName, int $index): string
    {
        $names = [
            'Main Portfolio',
            'North Portfolio',
            'South Portfolio',
            'East Portfolio',
            'West Portfolio',
            'Central Portfolio',
            'Residential Portfolio',
            'Commercial Portfolio',
        ];

        return $names[($index - 1) % count($names)] . ' - ' . $ownershipName;
    }

    /**
     * Generate unique portfolio code
     */
    private function generatePortfolioCode(int $ownershipId, int $index): string
    {
        return 'PORT-' . str_pad($ownershipId, 3, '0', STR_PAD_LEFT) . '-' . str_pad($index, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get random portfolio type
     */
    private function getRandomPortfolioType(): string
    {
        $types = ['general', 'residential', 'commercial', 'mixed', 'industrial'];
        return $types[array_rand($types)];
    }

    /**
     * Get building name
     */
    private function getBuildingName(int $index): string
    {
        $names = [
            'Tower A',
            'Tower B',
            'Tower C',
            'Building 1',
            'Building 2',
            'Complex A',
            'Complex B',
            'Residential Block',
            'Commercial Center',
            'Office Building',
        ];

        return $names[($index - 1) % count($names)];
    }

    /**
     * Generate unique building code
     */
    private function generateBuildingCode(int $portfolioId, int $index): string
    {
        return 'BLD-' . str_pad($portfolioId, 3, '0', STR_PAD_LEFT) . '-' . str_pad($index, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get random building type
     */
    private function getRandomBuildingType(): string
    {
        $types = ['residential', 'commercial', 'mixed', 'office', 'retail'];
        return $types[array_rand($types)];
    }

    /**
     * Get floor name
     */
    private function getFloorName(int $number): string
    {
        if ($number < 0) {
            return 'Basement ' . abs($number);
        } elseif ($number === 1) {
            return 'Ground Floor';
        } else {
            return 'Floor ' . $number;
        }
    }

    /**
     * Generate unit number
     */
    private function generateUnitNumber(int $floorNumber, int $unitIndex): string
    {
        $floorPrefix = $floorNumber < 0 ? 'B' . abs($floorNumber) : str_pad($floorNumber, 2, '0', STR_PAD_LEFT);
        return $floorPrefix . str_pad($unitIndex, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get random unit type
     */
    private function getRandomUnitType(): string
    {
        $types = ['apartment', 'office', 'shop', 'warehouse', 'studio', 'penthouse'];
        return $types[array_rand($types)];
    }

    /**
     * Get area for unit type
     */
    private function getAreaForUnitType(string $type): float
    {
        $areas = [
            'apartment' => rand(80, 200),
            'office' => rand(50, 300),
            'shop' => rand(30, 150),
            'warehouse' => rand(100, 500),
            'studio' => rand(40, 80),
            'penthouse' => rand(200, 500),
        ];

        return $areas[$type] ?? 100;
    }

    /**
     * Get prices for unit type
     */
    private function getPricesForUnitType(string $type, float $area): array
    {
        $pricePerSqm = [
            'apartment' => rand(40, 80),
            'office' => rand(60, 120),
            'shop' => rand(80, 150),
            'warehouse' => rand(20, 40),
            'studio' => rand(50, 90),
            'penthouse' => rand(100, 200),
        ];

        $monthly = ($pricePerSqm[$type] ?? 50) * $area;
        $quarterly = $monthly * 3 * 0.95; // 5% discount
        $yearly = $monthly * 12 * 0.90; // 10% discount

        return [
            'monthly' => round($monthly, 2),
            'quarterly' => round($quarterly, 2),
            'yearly' => round($yearly, 2),
        ];
    }

    /**
     * Get random unit status
     */
    private function getRandomUnitStatus(): string
    {
        $statuses = ['available', 'rented', 'maintenance', 'reserved', 'available', 'available']; // More available
        return $statuses[array_rand($statuses)];
    }

    /**
     * Create unit specifications
     */
    private function createUnitSpecifications(Unit $unit, string $unitType): void
    {
        $specs = [];

        if ($unitType === 'apartment' || $unitType === 'studio' || $unitType === 'penthouse') {
            $specs = [
                ['key' => 'bedrooms', 'value' => (string) rand(1, $unitType === 'penthouse' ? 5 : 3), 'type' => 'integer'],
                ['key' => 'bathrooms', 'value' => (string) rand(1, $unitType === 'penthouse' ? 4 : 2), 'type' => 'integer'],
                ['key' => 'balcony', 'value' => rand(0, 1) ? 'true' : 'false', 'type' => 'boolean'],
                ['key' => 'parking', 'value' => (string) rand(1, 2), 'type' => 'integer'],
                ['key' => 'furnished', 'value' => rand(0, 1) ? 'true' : 'false', 'type' => 'boolean'],
            ];
        } elseif ($unitType === 'office') {
            $specs = [
                ['key' => 'capacity', 'value' => (string) rand(5, 50), 'type' => 'integer'],
                ['key' => 'meeting_rooms', 'value' => (string) rand(0, 3), 'type' => 'integer'],
                ['key' => 'parking', 'value' => (string) rand(2, 10), 'type' => 'integer'],
                ['key' => 'furnished', 'value' => rand(0, 1) ? 'true' : 'false', 'type' => 'boolean'],
            ];
        } elseif ($unitType === 'shop') {
            $specs = [
                ['key' => 'storefront', 'value' => 'true', 'type' => 'boolean'],
                ['key' => 'parking', 'value' => (string) rand(1, 3), 'type' => 'integer'],
                ['key' => 'storage', 'value' => rand(0, 1) ? 'true' : 'false', 'type' => 'boolean'],
            ];
        } elseif ($unitType === 'warehouse') {
            $specs = [
                ['key' => 'loading_dock', 'value' => rand(0, 1) ? 'true' : 'false', 'type' => 'boolean'],
                ['key' => 'ceiling_height', 'value' => (string) rand(5, 10), 'type' => 'integer'],
                ['key' => 'security', 'value' => 'true', 'type' => 'boolean'],
            ];
        }

        foreach ($specs as $spec) {
            UnitSpecification::create([
                'unit_id' => $unit->id,
                'key' => $spec['key'],
                'value' => $spec['value'],
                'type' => $spec['type'],
            ]);
        }
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

    /**
     * Get latitude for city
     */
    private function getLatitudeForCity(string $city): float
    {
        $latitudes = [
            'Riyadh' => 24.7136,
            'Jeddah' => 21.4858,
            'Dammam' => 26.4207,
            'Mecca' => 21.3891,
            'Medina' => 24.5247,
            'Khobar' => 26.2172,
        ];

        return $latitudes[$city] ?? 24.7136 + (rand(-100, 100) / 1000);
    }

    /**
     * Get longitude for city
     */
    private function getLongitudeForCity(string $city): float
    {
        $longitudes = [
            'Riyadh' => 46.6753,
            'Jeddah' => 39.1925,
            'Dammam' => 50.0888,
            'Mecca' => 39.8579,
            'Medina' => 39.5692,
            'Khobar' => 50.1971,
        ];

        return $longitudes[$city] ?? 46.6753 + (rand(-100, 100) / 1000);
    }
}
