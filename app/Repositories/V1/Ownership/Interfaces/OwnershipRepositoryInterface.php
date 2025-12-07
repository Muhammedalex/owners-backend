<?php

namespace App\Repositories\V1\Ownership\Interfaces;

use App\Models\V1\Ownership\Ownership;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OwnershipRepositoryInterface
{
    /**
     * Get all ownerships with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all ownerships.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find ownership by ID.
     */
    public function find(int $id): ?Ownership;

    /**
     * Find ownership by UUID.
     */
    public function findByUuid(string $uuid): ?Ownership;

    /**
     * Create a new ownership.
     */
    public function create(array $data): Ownership;

    /**
     * Update ownership.
     */
    public function update(Ownership $ownership, array $data): Ownership;

    /**
     * Delete ownership.
     */
    public function delete(Ownership $ownership): bool;

    /**
     * Activate ownership.
     */
    public function activate(Ownership $ownership): Ownership;

    /**
     * Deactivate ownership.
     */
    public function deactivate(Ownership $ownership): Ownership;
}

