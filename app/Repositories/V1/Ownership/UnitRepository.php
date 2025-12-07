<?php

namespace App\Repositories\V1\Ownership;

use App\Models\V1\Ownership\Unit;
use App\Repositories\V1\Ownership\Interfaces\UnitRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UnitRepository implements UnitRepositoryInterface
{
    /**
     * Get all units with pagination.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Unit::query();

        // Ownership scope is MANDATORY - must be provided
        if (!isset($filters['ownership_id'])) {
            throw new \InvalidArgumentException('Ownership ID is required for unit queries.');
        }
        $query->forOwnership($filters['ownership_id']);

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['status'])) {
            $query->ofStatus($filters['status']);
        }

        if (isset($filters['building_id'])) {
            $query->forBuilding($filters['building_id']);
        }

        if (isset($filters['floor_id'])) {
            $query->forFloor($filters['floor_id']);
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        return $query->with(['ownership', 'building.portfolio', 'floor', 'specifications'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all units.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function all(array $filters = []): Collection
    {
        $query = Unit::query();

        // Ownership scope is MANDATORY - must be provided
        if (!isset($filters['ownership_id'])) {
            throw new \InvalidArgumentException('Ownership ID is required for unit queries.');
        }
        $query->forOwnership($filters['ownership_id']);

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['status'])) {
            $query->ofStatus($filters['status']);
        }

        if (isset($filters['building_id'])) {
            $query->forBuilding($filters['building_id']);
        }

        if (isset($filters['floor_id'])) {
            $query->forFloor($filters['floor_id']);
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        return $query->with(['ownership', 'building.portfolio', 'floor', 'specifications'])
            ->latest()
            ->get();
    }

    /**
     * Find unit by ID.
     */
    public function find(int $id): ?Unit
    {
        return Unit::with(['ownership', 'building.portfolio', 'floor', 'specifications'])->find($id);
    }

    /**
     * Find unit by UUID.
     */
    public function findByUuid(string $uuid): ?Unit
    {
        return Unit::where('uuid', $uuid)
            ->with(['ownership', 'building.portfolio', 'floor', 'specifications'])
            ->first();
    }

    /**
     * Find unit by number.
     */
    public function findByNumber(string $number, int $buildingId): ?Unit
    {
        return Unit::where('number', $number)
            ->where('building_id', $buildingId)
            ->with(['ownership', 'building.portfolio', 'floor', 'specifications'])
            ->first();
    }

    /**
     * Create a new unit.
     */
    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    /**
     * Update unit.
     */
    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);
        return $unit->fresh(['ownership', 'building.portfolio', 'floor', 'specifications']);
    }

    /**
     * Delete unit.
     */
    public function delete(Unit $unit): bool
    {
        return $unit->delete();
    }

    /**
     * Activate unit.
     */
    public function activate(Unit $unit): Unit
    {
        $unit->activate();
        return $unit->fresh(['ownership', 'building.portfolio', 'floor', 'specifications']);
    }

    /**
     * Deactivate unit.
     */
    public function deactivate(Unit $unit): Unit
    {
        $unit->deactivate();
        return $unit->fresh(['ownership', 'building.portfolio', 'floor', 'specifications']);
    }
}

