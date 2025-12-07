<?php

namespace App\Repositories\V1\Ownership\Interfaces;

use App\Models\V1\Ownership\Building;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BuildingRepositoryInterface
{
    /**
     * Get all buildings with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all buildings.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find building by ID.
     */
    public function find(int $id): ?Building;

    /**
     * Find building by UUID.
     */
    public function findByUuid(string $uuid): ?Building;

    /**
     * Find building by code.
     */
    public function findByCode(string $code): ?Building;

    /**
     * Create a new building.
     */
    public function create(array $data): Building;

    /**
     * Update building.
     */
    public function update(Building $building, array $data): Building;

    /**
     * Delete building.
     */
    public function delete(Building $building): bool;

    /**
     * Activate building.
     */
    public function activate(Building $building): Building;

    /**
     * Deactivate building.
     */
    public function deactivate(Building $building): Building;
}

