<?php

namespace App\Repositories\V1\Tenant;

use App\Models\V1\Tenant\Tenant;
use App\Repositories\V1\Tenant\Interfaces\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TenantRepository implements TenantRepositoryInterface
{
    /**
     * Get all tenants with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Tenant::query();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('national_id', 'like', "%{$search}%")
                    ->orWhere('emergency_name', 'like', "%{$search}%")
                    ->orWhere('emergency_phone', 'like', "%{$search}%")
                    ->orWhere('employer', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first', 'like', "%{$search}%")
                            ->orWhere('last', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['rating'])) {
            $query->withRating($filters['rating']);
        }

        if (isset($filters['employment'])) {
            $query->withEmployment($filters['employment']);
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['user', 'ownership'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all tenants.
     */
    public function all(array $filters = []): Collection
    {
        $query = Tenant::query();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('national_id', 'like', "%{$search}%")
                    ->orWhere('emergency_name', 'like', "%{$search}%")
                    ->orWhere('emergency_phone', 'like', "%{$search}%")
                    ->orWhere('employer', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first', 'like', "%{$search}%")
                            ->orWhere('last', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['rating'])) {
            $query->withRating($filters['rating']);
        }

        if (isset($filters['employment'])) {
            $query->withEmployment($filters['employment']);
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['user', 'ownership'])
            ->latest()
            ->get();
    }

    /**
     * Find tenant by ID.
     */
    public function find(int $id): ?Tenant
    {
        return Tenant::with(['user', 'ownership'])
            ->find($id);
    }

    /**
     * Find tenant by user ID.
     */
    public function findByUserId(int $userId): ?Tenant
    {
        return Tenant::where('user_id', $userId)
            ->with(['user', 'ownership'])
            ->first();
    }

    /**
     * Find tenant by user ID and ownership ID.
     */
    public function findByUserAndOwnership(int $userId, int $ownershipId): ?Tenant
    {
        return Tenant::where('user_id', $userId)
            ->where('ownership_id', $ownershipId)
            ->with(['user', 'ownership'])
            ->first();
    }

    /**
     * Create a new tenant.
     */
    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    /**
     * Update tenant.
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);
        return $tenant->fresh(['user', 'ownership']);
    }

    /**
     * Delete tenant.
     */
    public function delete(Tenant $tenant): bool
    {
        return $tenant->delete();
    }
}

