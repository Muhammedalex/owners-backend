<?php

namespace App\Repositories\V1\Ownership\Interfaces;

use App\Models\V1\Ownership\UserOwnershipMapping;
use Illuminate\Database\Eloquent\Collection;

interface UserOwnershipMappingRepositoryInterface
{
    /**
     * Get all mappings.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find mapping by ID.
     */
    public function find(int $id): ?UserOwnershipMapping;

    /**
     * Find mapping by user and ownership.
     */
    public function findByUserAndOwnership(int $userId, int $ownershipId): ?UserOwnershipMapping;

    /**
     * Get mappings for user.
     */
    public function getByUser(int $userId): Collection;

    /**
     * Get mappings for ownership.
     */
    public function getByOwnership(int $ownershipId): Collection;

    /**
     * Get default mapping for user.
     */
    public function getDefaultForUser(int $userId): ?UserOwnershipMapping;

    /**
     * Create a new mapping.
     */
    public function create(array $data): UserOwnershipMapping;

    /**
     * Update mapping.
     */
    public function update(UserOwnershipMapping $mapping, array $data): UserOwnershipMapping;

    /**
     * Delete mapping.
     */
    public function delete(UserOwnershipMapping $mapping): bool;

    /**
     * Set as default ownership for user.
     */
    public function setAsDefault(UserOwnershipMapping $mapping): UserOwnershipMapping;
}

