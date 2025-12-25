<?php

namespace App\Repositories\V1\Auth;

use App\Models\V1\Auth\User;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get all users with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = [], ?User $currentUser = null): LengthAwarePaginator
    {
        $query = User::query();

        // If current user is not Super Admin, exclude Super Admin users
        if ($currentUser && !$currentUser->isSuperAdmin()) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->withoutGlobalScope(\App\Models\Scopes\ExcludeSystemRolesScope::class)
                  ->where('name', 'Super Admin');
            });
        }

        // Apply filters
        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first', 'like', "%{$search}%")
                    ->orWhere('last', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (isset($filters['verified'])) {
            if ($filters['verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Filter by ownership_id if provided (for users with view.own permission)
        if (isset($filters['ownership_id'])) {
            $query->whereHas('ownershipMappings', function ($q) use ($filters) {
                $q->where('ownership_id', $filters['ownership_id']);
            });
        }

        return $query->with('roles')->latest()->paginate($perPage);
    }

    /**
     * Get all users.
     */
    public function all(array $filters = [], ?User $currentUser = null): Collection
    {
        $query = User::query();

        // If current user is not Super Admin, exclude Super Admin users
        if ($currentUser && !$currentUser->isSuperAdmin()) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->withoutGlobalScope(\App\Models\Scopes\ExcludeSystemRolesScope::class)
                  ->where('name', 'Super Admin');
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }
        if (isset($filters['ownership_id'])) {
            $query->whereHas('ownershipMappings', function ($q) use ($filters) {
                $q->where('ownership_id', $filters['ownership_id']);
            });
        }
        return $query->latest()->get();
    }

    /**
     * Find user by ID.
     */
    public function find(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    /**
     * Find user by UUID.
     */
    public function findByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->first();
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find user by phone.
     */
    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        // Password will be automatically hashed by User model's 'hashed' cast
        // No need to hash manually here

        // Ensure UUID is set if not provided (trait should handle this, but as fallback)
        if (!isset($data['uuid']) || empty($data['uuid'])) {
            $data['uuid'] = (string) \Illuminate\Support\Str::uuid();
        }

        return User::create($data);
    }

    /**
     * Update user.
     */
    public function update(User $user, array $data): User
    {
        // Password will be automatically hashed by User model's 'hashed' cast
        // No need to hash manually here

        $user->update($data);

        return $user->fresh();
    }

    /**
     * Delete user.
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Activate user.
     */
    public function activate(User $user): User
    {
        $user->update(['active' => true]);

        return $user->fresh();
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): User
    {
        $user->update(['active' => false]);

        return $user->fresh();
    }

    /**
     * Get users by ownership ID, excluding users already mapped to another ownership.
     *
     * @param int $ownershipId Source ownership ID
     * @param array $excludeUserIds User IDs to exclude (users already in target ownership)
     * @return Collection
     */
    public function getUsersByOwnership(int $ownershipId, array $excludeUserIds = []): Collection
    {
        $query = User::whereHas('ownershipMappings', function ($q) use ($ownershipId) {
            $q->where('ownership_id', $ownershipId);
        });

        if (!empty($excludeUserIds)) {
            $query->whereNotIn('id', $excludeUserIds);
        }

        return $query->with('roles')->latest()->get();
    }
}

