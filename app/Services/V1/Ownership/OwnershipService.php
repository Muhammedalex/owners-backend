<?php

namespace App\Services\V1\Ownership;

use App\Models\V1\Ownership\Ownership;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OwnershipService
{
    public function __construct(
        private OwnershipRepositoryInterface $ownershipRepository
    ) {}

    /**
     * Get all ownerships with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->ownershipRepository->paginate($perPage, $filters);
    }

    /**
     * Get all ownerships.
     */
    public function all(array $filters = []): Collection
    {
        return $this->ownershipRepository->all($filters);
    }

    /**
     * Find ownership by ID.
     */
    public function find(int $id): ?Ownership
    {
        return $this->ownershipRepository->find($id);
    }

    /**
     * Find ownership by UUID.
     */
    public function findByUuid(string $uuid): ?Ownership
    {
        return $this->ownershipRepository->findByUuid($uuid);
    }

    /**
     * Create a new ownership.
     */
    public function create(array $data): Ownership
    {
        return DB::transaction(function () use ($data) {
            return $this->ownershipRepository->create($data);
        });
    }

    /**
     * Update ownership.
     */
    public function update(Ownership $ownership, array $data): Ownership
    {
        return DB::transaction(function () use ($ownership, $data) {
            return $this->ownershipRepository->update($ownership, $data);
        });
    }

    /**
     * Delete ownership.
     */
    public function delete(Ownership $ownership): bool
    {
        return DB::transaction(function () use ($ownership) {
            return $this->ownershipRepository->delete($ownership);
        });
    }

    /**
     * Activate ownership.
     */
    public function activate(Ownership $ownership): Ownership
    {
        return $this->ownershipRepository->activate($ownership);
    }

    /**
     * Deactivate ownership.
     */
    public function deactivate(Ownership $ownership): Ownership
    {
        return $this->ownershipRepository->deactivate($ownership);
    }
}

