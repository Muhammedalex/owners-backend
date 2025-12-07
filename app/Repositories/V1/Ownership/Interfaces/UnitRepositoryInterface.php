<?php

namespace App\Repositories\V1\Ownership\Interfaces;

use App\Models\V1\Ownership\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UnitRepositoryInterface
{
    /**
     * Get all units with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all units.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find unit by ID.
     */
    public function find(int $id): ?Unit;

    /**
     * Find unit by UUID.
     */
    public function findByUuid(string $uuid): ?Unit;

    /**
     * Find unit by number.
     */
    public function findByNumber(string $number, int $buildingId): ?Unit;

    /**
     * Create a new unit.
     */
    public function create(array $data): Unit;

    /**
     * Update unit.
     */
    public function update(Unit $unit, array $data): Unit;

    /**
     * Delete unit.
     */
    public function delete(Unit $unit): bool;

    /**
     * Activate unit.
     */
    public function activate(Unit $unit): Unit;

    /**
     * Deactivate unit.
     */
    public function deactivate(Unit $unit): Unit;
}

