<?php

namespace App\Repositories\V1\Ownership;

use App\Models\V1\Ownership\Building;
use App\Models\V1\Ownership\BuildingFloor;
use App\Repositories\V1\Ownership\Interfaces\BuildingFloorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BuildingFloorRepository implements BuildingFloorRepositoryInterface
{
    /**
     * Get all building floors with pagination.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = BuildingFloor::query();

        // Ownership scope is MANDATORY - must be provided
        if (!isset($filters['ownership_id'])) {
            throw new \InvalidArgumentException('Ownership ID is required for building floor queries.');
        }

        // Join with buildings to filter by ownership
        $query->join('buildings', 'building_floors.building_id', '=', 'buildings.id')
            ->where('buildings.ownership_id', $filters['ownership_id'])
            ->select('building_floors.*');

        // Apply filters
        if (isset($filters['building_id'])) {
            $query->where('building_floors.building_id', $filters['building_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('building_floors.name', 'like', "%{$search}%")
                    ->orWhere('building_floors.description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->where('building_floors.active', true);
            } else {
                $query->where('building_floors.active', false);
            }
        }

        return $query->with(['building.ownership', 'building.portfolio'])
            ->orderBy('building_floors.number')
            ->paginate($perPage);
    }

    /**
     * Get all building floors.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function all(array $filters = []): Collection
    {
        $query = BuildingFloor::query();

        // Ownership scope is MANDATORY - must be provided
        if (!isset($filters['ownership_id'])) {
            throw new \InvalidArgumentException('Ownership ID is required for building floor queries.');
        }

        // Join with buildings to filter by ownership
        $query->join('buildings', 'building_floors.building_id', '=', 'buildings.id')
            ->where('buildings.ownership_id', $filters['ownership_id'])
            ->select('building_floors.*');

        // Apply filters
        if (isset($filters['building_id'])) {
            $query->where('building_floors.building_id', $filters['building_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('building_floors.name', 'like', "%{$search}%")
                    ->orWhere('building_floors.description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->where('building_floors.active', true);
            } else {
                $query->where('building_floors.active', false);
            }
        }

        return $query->with(['building.ownership', 'building.portfolio'])
            ->orderBy('building_floors.number')
            ->get();
    }

    /**
     * Find building floor by ID.
     */
    public function find(int $id): ?BuildingFloor
    {
        return BuildingFloor::with(['building.ownership', 'building.portfolio'])->find($id);
    }

    /**
     * Create a new building floor.
     */
    public function create(array $data): BuildingFloor
    {
        return BuildingFloor::create($data);
    }

    /**
     * Update building floor.
     */
    public function update(BuildingFloor $buildingFloor, array $data): BuildingFloor
    {
        $buildingFloor->update($data);
        return $buildingFloor->fresh(['building.ownership', 'building.portfolio']);
    }

    /**
     * Delete building floor.
     */
    public function delete(BuildingFloor $buildingFloor): bool
    {
        return $buildingFloor->delete();
    }

    /**
     * Activate building floor.
     */
    public function activate(BuildingFloor $buildingFloor): BuildingFloor
    {
        $buildingFloor->activate();
        return $buildingFloor->fresh(['building.ownership', 'building.portfolio']);
    }

    /**
     * Deactivate building floor.
     */
    public function deactivate(BuildingFloor $buildingFloor): BuildingFloor
    {
        $buildingFloor->deactivate();
        return $buildingFloor->fresh(['building.ownership', 'building.portfolio']);
    }
}

