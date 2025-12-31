<?php

namespace App\Repositories\V1\Auth;

use App\Models\V1\Auth\Permission;
use App\Models\V1\Auth\User;
use App\Repositories\V1\Auth\Interfaces\PermissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PermissionRepository implements PermissionRepositoryInterface
{
    /**
     * Permissions to temporarily exclude from API responses for non-superadmin users.
     * TODO: Remove this when business needs change.
     */
    private const TEMPORARILY_HIDDEN_PERMISSIONS = [
        'ownerships.create',
        'ownerships.switch',
    ];

    /**
     * Get all permissions with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Permission::query();

        // If current user is not Super Admin, exclude temporarily hidden permissions
        $currentUser = Auth::user();
        if ($currentUser instanceof User && !$currentUser->isSuperAdmin()) {
            $query->whereNotIn('name', self::TEMPORARILY_HIDDEN_PERMISSIONS);
        }

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }

        if (isset($filters['module'])) {
            $module = $filters['module'];
            $query->where('name', 'like', "{$module}.%");
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get all permissions.
     */
    public function all(array $filters = []): Collection
    {
        $query = Permission::query();

        // If current user is not Super Admin, exclude temporarily hidden permissions
        $currentUser = Auth::user();
        if ($currentUser instanceof User && !$currentUser->isSuperAdmin()) {
            $query->whereNotIn('name', self::TEMPORARILY_HIDDEN_PERMISSIONS);
        }

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }

        if (isset($filters['module'])) {
            $module = $filters['module'];
            $query->where('name', 'like', "{$module}.%");
        }

        return $query->latest()->get();
    }

    /**
     * Find permission by ID.
     */
    public function find(int $id): ?Permission
    {
        return Permission::find($id);
    }

    /**
     * Find permission by name.
     */
    public function findByName(string $name, string $guardName = 'web'): ?Permission
    {
        return Permission::where('name', $name)
            ->where('guard_name', $guardName)
            ->first();
    }

    // Note: create, update, delete methods removed
    // Permissions are hard-coded in seeders and cannot be modified via API

    /**
     * Get permissions grouped by module.
     */
    public function getGroupedByModule(): array
    {
        $query = Permission::query();

        // If current user is not Super Admin, exclude temporarily hidden permissions
        $currentUser = Auth::user();
        if ($currentUser instanceof User && !$currentUser->isSuperAdmin()) {
            $query->whereNotIn('name', self::TEMPORARILY_HIDDEN_PERMISSIONS);
        }

        $permissions = $query->orderBy('name')->get();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'other';
            
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            $grouped[$module][] = $permission;
        }

        return $grouped;
    }
}

