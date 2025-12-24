<?php

namespace App\Repositories\V1\Auth\Interfaces;

use App\Models\V1\Auth\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all users with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all users.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find user by ID.
     */
    public function find(int $id): ?User;

    /**
     * Find user by UUID.
     */
    public function findByUuid(string $uuid): ?User;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by phone.
     */
    public function findByPhone(string $phone): ?User;

    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Update user.
     */
    public function update(User $user, array $data): User;

    /**
     * Delete user.
     */
    public function delete(User $user): bool;

    /**
     * Activate user.
     */
    public function activate(User $user): User;

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): User;

    /**
     * Get users by ownership ID, excluding users already mapped to another ownership.
     *
     * @param int $ownershipId Source ownership ID
     * @param array $excludeUserIds User IDs to exclude (users already in target ownership)
     * @return Collection
     */
    public function getUsersByOwnership(int $ownershipId, array $excludeUserIds = []): Collection;
}

