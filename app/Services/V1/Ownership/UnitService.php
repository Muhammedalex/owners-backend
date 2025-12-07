<?php

namespace App\Services\V1\Ownership;

use App\Models\V1\Ownership\Building;
use App\Models\V1\Ownership\Unit;
use App\Repositories\V1\Ownership\Interfaces\UnitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UnitService
{
    public function __construct(
        private UnitRepositoryInterface $unitRepository
    ) {}

    /**
     * Get all units with pagination.
     * Ownership scope is mandatory.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->unitRepository->paginate($perPage, $filters);
    }

    /**
     * Get all units.
     * Ownership scope is mandatory.
     */
    public function all(array $filters = []): Collection
    {
        return $this->unitRepository->all($filters);
    }

    /**
     * Find unit by ID.
     */
    public function find(int $id): ?Unit
    {
        return $this->unitRepository->find($id);
    }

    /**
     * Find unit by UUID.
     */
    public function findByUuid(string $uuid): ?Unit
    {
        return $this->unitRepository->findByUuid($uuid);
    }

    /**
     * Find unit by number.
     */
    public function findByNumber(string $number, int $buildingId): ?Unit
    {
        return $this->unitRepository->findByNumber($number, $buildingId);
    }

    /**
     * Create a new unit.
     * Ownership ID is mandatory and must be provided in data.
     */
    public function create(array $data): Unit
    {
        return DB::transaction(function () use ($data) {
            if (!isset($data['ownership_id'])) {
                throw new \InvalidArgumentException('Ownership ID is required to create a unit.');
            }

            // Verify building belongs to ownership
            $building = Building::where('id', $data['building_id'])
                ->where('ownership_id', $data['ownership_id'])
                ->firstOrFail();

            // Verify floor belongs to building if floor_id is provided
            if (isset($data['floor_id'])) {
                $floor = $building->buildingFloors()->where('id', $data['floor_id'])->firstOrFail();
            }

            return $this->unitRepository->create($data);
        });
    }

    /**
     * Update unit.
     */
    public function update(Unit $unit, array $data): Unit
    {
        return DB::transaction(function () use ($unit, $data) {
            // Verify building belongs to ownership if building_id is being updated
            if (isset($data['building_id'])) {
                $building = Building::where('id', $data['building_id'])
                    ->where('ownership_id', $unit->ownership_id)
                    ->firstOrFail();

                // Verify floor belongs to building if floor_id is provided
                if (isset($data['floor_id'])) {
                    $floor = $building->buildingFloors()->where('id', $data['floor_id'])->firstOrFail();
                }
            }

            return $this->unitRepository->update($unit, $data);
        });
    }

    /**
     * Delete unit.
     */
    public function delete(Unit $unit): bool
    {
        return DB::transaction(function () use ($unit) {
            return $this->unitRepository->delete($unit);
        });
    }

    /**
     * Activate unit.
     */
    public function activate(Unit $unit): Unit
    {
        return $this->unitRepository->activate($unit);
    }

    /**
     * Deactivate unit.
     */
    public function deactivate(Unit $unit): Unit
    {
        return $this->unitRepository->deactivate($unit);
    }
}

