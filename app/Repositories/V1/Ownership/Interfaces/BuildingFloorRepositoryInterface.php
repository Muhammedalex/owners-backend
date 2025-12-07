<?php

namespace App\Repositories\V1\Ownership\Interfaces;

use App\Models\V1\Ownership\BuildingFloor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BuildingFloorRepositoryInterface
{
    /**
     * Get all building floors with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all building floors.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find building floor by ID.
     */
    public function find(int $id): ?BuildingFloor;

    /**
     * Create a new building floor.
     */
    public function create(array $data): BuildingFloor;

    /**
     * Update building floor.
     */
    public function update(BuildingFloor $buildingFloor, array $data): BuildingFloor;

    /**
     * Delete building floor.
     */
    public function delete(BuildingFloor $buildingFloor): bool;

    /**
     * Activate building floor.
     */
    public function activate(BuildingFloor $buildingFloor): BuildingFloor;

    /**
     * Deactivate building floor.
     */
    public function deactivate(BuildingFloor $buildingFloor): BuildingFloor;
}

