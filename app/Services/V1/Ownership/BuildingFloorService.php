<?php

namespace App\Services\V1\Ownership;

use App\Models\V1\Ownership\Building;
use App\Models\V1\Ownership\BuildingFloor;
use App\Repositories\V1\Ownership\Interfaces\BuildingFloorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BuildingFloorService
{
    public function __construct(
        private BuildingFloorRepositoryInterface $buildingFloorRepository
    ) {}

    /**
     * Get all building floors with pagination.
     * Ownership scope is mandatory.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->buildingFloorRepository->paginate($perPage, $filters);
    }

    /**
     * Get all building floors.
     * Ownership scope is mandatory.
     */
    public function all(array $filters = []): Collection
    {
        return $this->buildingFloorRepository->all($filters);
    }

    /**
     * Find building floor by ID.
     */
    public function find(int $id): ?BuildingFloor
    {
        return $this->buildingFloorRepository->find($id);
    }

    /**
     * Create a new building floor.
     * Ownership ID is mandatory and must be provided in filters.
     */
    public function create(array $data, int $ownershipId): BuildingFloor
    {
        return DB::transaction(function () use ($data, $ownershipId) {
            // Verify building belongs to ownership
            $building = Building::where('id', $data['building_id'])
                ->where('ownership_id', $ownershipId)
                ->firstOrFail();

            return $this->buildingFloorRepository->create($data);
        });
    }

    /**
     * Update building floor.
     */
    public function update(BuildingFloor $buildingFloor, array $data, int $ownershipId): BuildingFloor
    {
        return DB::transaction(function () use ($buildingFloor, $data, $ownershipId) {
            // Verify building belongs to ownership if building_id is being updated
            if (isset($data['building_id'])) {
                $building = Building::where('id', $data['building_id'])
                    ->where('ownership_id', $ownershipId)
                    ->firstOrFail();
            }

            return $this->buildingFloorRepository->update($buildingFloor, $data);
        });
    }

    /**
     * Delete building floor.
     */
    public function delete(BuildingFloor $buildingFloor): bool
    {
        return DB::transaction(function () use ($buildingFloor) {
            return $this->buildingFloorRepository->delete($buildingFloor);
        });
    }

    /**
     * Activate building floor.
     */
    public function activate(BuildingFloor $buildingFloor): BuildingFloor
    {
        return $this->buildingFloorRepository->activate($buildingFloor);
    }

    /**
     * Deactivate building floor.
     */
    public function deactivate(BuildingFloor $buildingFloor): BuildingFloor
    {
        return $this->buildingFloorRepository->deactivate($buildingFloor);
    }
}

