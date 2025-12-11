<?php

namespace App\Services\V1\Tenant;

use App\Models\V1\Tenant\Tenant;
use App\Repositories\V1\Tenant\Interfaces\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepository
    ) {}

    /**
     * Get all tenants with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->tenantRepository->paginate($perPage, $filters);
    }

    /**
     * Get all tenants.
     */
    public function all(array $filters = []): Collection
    {
        return $this->tenantRepository->all($filters);
    }

    /**
     * Find tenant by ID.
     */
    public function find(int $id): ?Tenant
    {
        return $this->tenantRepository->find($id);
    }

    /**
     * Find tenant by user ID.
     */
    public function findByUserId(int $userId): ?Tenant
    {
        return $this->tenantRepository->findByUserId($userId);
    }

    /**
     * Find tenant by user ID and ownership ID.
     */
    public function findByUserAndOwnership(int $userId, int $ownershipId): ?Tenant
    {
        return $this->tenantRepository->findByUserAndOwnership($userId, $ownershipId);
    }

    /**
     * Create a new tenant.
     */
    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            return $this->tenantRepository->create($data);
        });
    }

    /**
     * Update tenant.
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            return $this->tenantRepository->update($tenant, $data);
        });
    }

    /**
     * Delete tenant.
     */
    public function delete(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            return $this->tenantRepository->delete($tenant);
        });
    }
}

