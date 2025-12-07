<?php

namespace App\Repositories\V1\Ownership;

use App\Models\V1\Ownership\UserOwnershipMapping;
use App\Repositories\V1\Ownership\Interfaces\UserOwnershipMappingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserOwnershipMappingRepository implements UserOwnershipMappingRepositoryInterface
{
    /**
     * Get all mappings.
     */
    public function all(array $filters = []): Collection
    {
        $query = UserOwnershipMapping::query();

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['default'])) {
            if ($filters['default']) {
                $query->default();
            } else {
                $query->where('default', false);
            }
        }

        return $query->with(['user', 'ownership'])
            ->latest('created_at')
            ->get();
    }

    /**
     * Find mapping by ID.
     */
    public function find(int $id): ?UserOwnershipMapping
    {
        return UserOwnershipMapping::with(['user', 'ownership'])->find($id);
    }

    /**
     * Find mapping by user and ownership.
     */
    public function findByUserAndOwnership(int $userId, int $ownershipId): ?UserOwnershipMapping
    {
        return UserOwnershipMapping::where('user_id', $userId)
            ->where('ownership_id', $ownershipId)
            ->with(['user', 'ownership'])
            ->first();
    }

    /**
     * Get mappings for user.
     */
    public function getByUser(int $userId): Collection
    {
        return UserOwnershipMapping::where('user_id', $userId)
            ->with(['ownership'])
            ->latest('created_at')
            ->get();
    }

    /**
     * Get mappings for ownership.
     */
    public function getByOwnership(int $ownershipId): Collection
    {
        return UserOwnershipMapping::where('ownership_id', $ownershipId)
            ->with(['user'])
            ->latest('created_at')
            ->get();
    }

    /**
     * Get default mapping for user.
     */
    public function getDefaultForUser(int $userId): ?UserOwnershipMapping
    {
        return UserOwnershipMapping::where('user_id', $userId)
            ->where('default', true)
            ->with(['ownership'])
            ->first();
    }

    /**
     * Create a new mapping.
     */
    public function create(array $data): UserOwnershipMapping
    {
        // If this is set as default, remove default from other mappings
        if (isset($data['default']) && $data['default']) {
            UserOwnershipMapping::where('user_id', $data['user_id'])
                ->update(['default' => false]);
        }

        // Add created_at manually since timestamps are disabled
        if (!isset($data['created_at'])) {
            $data['created_at'] = now();
        }

        return UserOwnershipMapping::create($data);
    }

    /**
     * Update mapping.
     */
    public function update(UserOwnershipMapping $mapping, array $data): UserOwnershipMapping
    {
        // If setting as default, remove default from other mappings
        if (isset($data['default']) && $data['default'] && !$mapping->isDefault()) {
            UserOwnershipMapping::where('user_id', $mapping->user_id)
                ->where('id', '!=', $mapping->id)
                ->update(['default' => false]);
        }

        $mapping->update($data);
        return $mapping->fresh(['user', 'ownership']);
    }

    /**
     * Delete mapping.
     */
    public function delete(UserOwnershipMapping $mapping): bool
    {
        return $mapping->delete();
    }

    /**
     * Set as default ownership for user.
     */
    public function setAsDefault(UserOwnershipMapping $mapping): UserOwnershipMapping
    {
        $mapping->setAsDefault();
        return $mapping->fresh(['user', 'ownership']);
    }
}

