<?php

namespace App\Repositories\V1\Ownership;

use App\Models\V1\Ownership\Building;
use App\Repositories\V1\Ownership\Interfaces\BuildingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BuildingRepository implements BuildingRepositoryInterface
{
    /**
     * Get all buildings with pagination.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Building::query();

        // Ownership scope is MANDATORY - must be provided
        if (!isset($filters['ownership_id'])) {
            throw new \InvalidArgumentException('Ownership ID is required for building queries.');
        }
        $query->forOwnership($filters['ownership_id']);

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['portfolio_id'])) {
            $query->forPortfolio($filters['portfolio_id']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (isset($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        return $query->with(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all buildings.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function all(array $filters = []): Collection
    {
        $query = Building::query();

        // Ownership scope is MANDATORY - must be provided
        if (!isset($filters['ownership_id'])) {
            throw new \InvalidArgumentException('Ownership ID is required for building queries.');
        }
        $query->forOwnership($filters['ownership_id']);

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['portfolio_id'])) {
            $query->forPortfolio($filters['portfolio_id']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (isset($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        return $query->with(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])
            ->latest()
            ->get();
    }

    /**
     * Find building by ID.
     */
    public function find(int $id): ?Building
    {
        return Building::with(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])
            ->find($id);
    }

    /**
     * Find building by UUID.
     */
    public function findByUuid(string $uuid): ?Building
    {
        return Building::where('uuid', $uuid)
            ->with(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])
            ->first();
    }

    /**
     * Find building by code.
     */
    public function findByCode(string $code): ?Building
    {
        return Building::where('code', $code)
            ->with(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])
            ->first();
    }

    /**
     * Create a new building.
     */
    public function create(array $data): Building
    {
        return Building::create($data);
    }

    /**
     * Update building.
     */
    public function update(Building $building, array $data): Building
    {
        $building->update($data);
        return $building->fresh(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors']);
    }

    /**
     * Delete building.
     */
    public function delete(Building $building): bool
    {
        return $building->delete();
    }

    /**
     * Activate building.
     */
    public function activate(Building $building): Building
    {
        $building->activate();
        return $building->fresh(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors']);
    }

    /**
     * Deactivate building.
     */
    public function deactivate(Building $building): Building
    {
        $building->deactivate();
        return $building->fresh(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors']);
    }
}

