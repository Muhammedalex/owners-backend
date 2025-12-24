<?php

namespace App\Services\V1\Ownership;

use App\Models\V1\Ownership\Building;
use App\Models\V1\Ownership\BuildingFloor;
use App\Models\V1\Ownership\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class UnitImportService
{
    protected int $ownershipId;
    protected array $errors = [];
    protected array $warnings = [];
    protected int $successCount = 0;
    protected int $skipCount = 0;
    protected ?int $buildingId = null; // Optional: if building is pre-selected

    public function __construct(int $ownershipId, ?int $buildingId = null)
    {
        $this->ownershipId = $ownershipId;
        $this->buildingId = $buildingId;
    }

    /**
     * Import units from collection
     */
    public function import(Collection $rows, bool $skipErrors = false): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because Excel starts at 1 and has header

                try {
                    $this->importRow($row, $rowNumber, $skipErrors);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'message' => $e->getMessage(),
                        'data' => $row->toArray(),
                    ];

                    if (!$skipErrors) {
                        DB::rollBack();
                        throw $e;
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    protected function importRow($row, int $rowNumber, bool $skipErrors): void
    {
        // 1. Validate and resolve building
        $building = $this->resolveBuilding($row, $rowNumber);
        if (!$building) {
            throw new \Exception("Building not found or invalid");
        }

        // 2. Validate and resolve floor (optional)
        $floor = null;
        if (!empty($row['floor_number']) || !empty($row['floor'])) {
            $floorNumber = $row['floor_number'] ?? $row['floor'];
            $floor = $this->resolveFloor($building->id, $floorNumber, $rowNumber);
        }

        // 3. Check for duplicate unit number
        $unitNumber = $row['unit_number'] ?? $row['number'] ?? null;
        if (!$unitNumber) {
            throw new \Exception("Unit number is required");
        }

        $existingUnit = Unit::where('building_id', $building->id)
            ->where('number', $unitNumber)
            ->first();

        if ($existingUnit) {
            if ($skipErrors) {
                $this->warnings[] = "Row {$rowNumber}: Unit {$unitNumber} already exists, skipped";
                $this->skipCount++;
                return;
            }
            throw new \Exception("Unit number already exists: {$unitNumber}");
        }

        // 4. Validate and prepare unit data
        $unitType = $row['unit_type'] ?? $row['type'] ?? null;
        if (!$unitType) {
            throw new \Exception("Unit type is required");
        }

        // Validate unit type
        $validTypes = ['apartment', 'office', 'shop', 'warehouse', 'studio', 'villa', 'penthouse'];
        if (!in_array(strtolower($unitType), $validTypes)) {
            throw new \Exception("Invalid unit type: {$unitType}. Valid types are: " . implode(', ', $validTypes));
        }

        // Excel converts "Area (m²)" to "area_m2" or "area (m²)" or "area"
        $area = $this->parseNumeric(
            $row['area'] 
            ?? $row['area_m2'] 
            ?? $row['area (m²)']
            ?? $row['area_m']
            ?? null
        );
        if (!$area || $area <= 0) {
            throw new \Exception("Area must be a positive number");
        }

        // 5. Prepare unit data
        $unitData = [
            'building_id' => $building->id,
            'floor_id' => $floor?->id,
            'ownership_id' => $this->ownershipId,
            'number' => $unitNumber,
            'type' => strtolower($unitType),
            'name' => $row['unit_name'] ?? $row['name'] ?? null,
            'area' => $area,
            'price_monthly' => $this->parseNumeric($row['price_monthly'] ?? null),
            'price_quarterly' => $this->parseNumeric($row['price_quarterly'] ?? null),
            'price_yearly' => $this->parseNumeric($row['price_yearly'] ?? null),
            'status' => 'available', // Default status
            'active' => true, // Default active
        ];

        // 6. Create unit
        $unit = Unit::create($unitData);

        $this->successCount++;
    }

    protected function resolveBuilding($row, int $rowNumber): ?Building
    {
        // If building is pre-selected, use it
        if ($this->buildingId) {
            $building = Building::where('id', $this->buildingId)
                ->where('ownership_id', $this->ownershipId)
                ->first();
            
            if (!$building) {
                throw new \Exception("Pre-selected building not found");
            }
            
            return $building;
        }

        // Otherwise, resolve from row data
        // Excel converts "Building Code/Name" to "building_code_name" or "building_code/name"
        $identifier = $row['building_code_name'] 
            ?? $row['building_code/name']
            ?? $row['building_code'] 
            ?? $row['building_name'] 
            ?? $row['building'] 
            ?? null;

        if (!$identifier) {
            throw new \Exception("Building identifier is required (code or name)");
        }

        $building = Building::where('ownership_id', $this->ownershipId)
            ->where(function ($query) use ($identifier) {
                $query->where('code', $identifier)
                    ->orWhere('name', $identifier);
            })
            ->first();

        if (!$building) {
            throw new \Exception("Building not found: {$identifier}. Please check the building code or name.");
        }

        return $building;
    }

    protected function resolveFloor(int $buildingId, $floorNumber, int $rowNumber): ?BuildingFloor
    {
        if ($floorNumber === null || $floorNumber === '') {
            return null;
        }

        $floor = BuildingFloor::where('building_id', $buildingId)
            ->where('number', $floorNumber)
            ->first();

        if (!$floor) {
            // Optionally create floor if it doesn't exist
            // For now, we'll throw an error
            throw new \Exception("Floor number {$floorNumber} not found in building. Please create the floor first or use a valid floor number.");
        }

        return $floor;
    }


    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['true', '1', 'yes', 'y', 'نعم']);
        }
        return (bool) $value;
    }

    protected function parseNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        // Remove any non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', (string) $value);
        return $cleaned !== '' ? (float) $cleaned : null;
    }
}

