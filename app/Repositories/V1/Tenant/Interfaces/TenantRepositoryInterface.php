<?php

namespace App\Repositories\V1\Tenant\Interfaces;

use App\Models\V1\Tenant\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TenantRepositoryInterface
{
    /**
     * Get all tenants with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all tenants.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find tenant by ID.
     */
    public function find(int $id): ?Tenant;

    /**
     * Find tenant by user ID.
     */
    public function findByUserId(int $userId): ?Tenant;

    /**
     * Find tenant by user ID and ownership ID.
     */
    public function findByUserAndOwnership(int $userId, int $ownershipId): ?Tenant;

    /**
     * Create a new tenant.
     */
    public function create(array $data): Tenant;

    /**
     * Update tenant.
     */
    public function update(Tenant $tenant, array $data): Tenant;

    /**
     * Delete tenant.
     */
    public function delete(Tenant $tenant): bool;
}

