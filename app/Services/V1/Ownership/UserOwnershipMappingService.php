<?php

namespace App\Services\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\UserOwnershipMapping;
use App\Repositories\V1\Ownership\Interfaces\UserOwnershipMappingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserOwnershipMappingService
{
    public function __construct(
        private UserOwnershipMappingRepositoryInterface $mappingRepository
    ) {}

    /**
     * Get all mappings.
     * 
     * @param array $filters
     * @param User|null $currentUser Current authenticated user (for filtering super admin users)
     */
    public function all(array $filters = [], ?User $currentUser = null): Collection
    {
        return $this->mappingRepository->all($filters, $currentUser);
    }

    /**
     * Find mapping by ID.
     */
    public function find(int $id): ?UserOwnershipMapping
    {
        return $this->mappingRepository->find($id);
    }

    /**
     * Find mapping by user and ownership.
     */
    public function findByUserAndOwnership(int $userId, int $ownershipId): ?UserOwnershipMapping
    {
        return $this->mappingRepository->findByUserAndOwnership($userId, $ownershipId);
    }

    /**
     * Get mappings for user.
     */
    public function getByUser(int $userId): Collection
    {
        return $this->mappingRepository->getByUser($userId);
    }

    /**
     * Get mappings for ownership.
     */
    public function getByOwnership(int $ownershipId): Collection
    {
        return $this->mappingRepository->getByOwnership($ownershipId);
    }

    /**
     * Get default mapping for user.
     */
    public function getDefaultForUser(int $userId): ?UserOwnershipMapping
    {
        return $this->mappingRepository->getDefaultForUser($userId);
    }

    /**
     * Create a new mapping.
     */
    public function create(array $data): UserOwnershipMapping
    {
        return DB::transaction(function () use ($data) {
            // Check if mapping already exists
            $existing = $this->mappingRepository->findByUserAndOwnership(
                $data['user_id'],
                $data['ownership_id']
            );

            if ($existing) {
                throw new \Exception('User is already mapped to this ownership.');
            }

            return $this->mappingRepository->create($data);
        });
    }

    /**
     * Update mapping.
     */
    public function update(UserOwnershipMapping $mapping, array $data): UserOwnershipMapping
    {
        return DB::transaction(function () use ($mapping, $data) {
            return $this->mappingRepository->update($mapping, $data);
        });
    }

    /**
     * Delete mapping.
     */
    public function delete(UserOwnershipMapping $mapping): bool
    {
        return DB::transaction(function () use ($mapping) {
            return $this->mappingRepository->delete($mapping);
        });
    }

    /**
     * Set as default ownership for user.
     */
    public function setAsDefault(UserOwnershipMapping $mapping): UserOwnershipMapping
    {
        return DB::transaction(function () use ($mapping) {
            return $this->mappingRepository->setAsDefault($mapping);
        });
    }
}

