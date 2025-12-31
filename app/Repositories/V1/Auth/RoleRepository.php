<?php

namespace App\Repositories\V1\Auth;

use App\Models\V1\Auth\Role;
use App\Models\V1\Auth\User;
use App\Repositories\V1\Auth\Interfaces\RoleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Get all roles with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = [], ?User $currentUser = null): LengthAwarePaginator
    {
        $query = Role::query();

        // If current user is not Super Admin, exclude Super Admin role
        if ($currentUser && !$currentUser->isSuperAdmin()) {
            $query->where('name', '!=', 'Super Admin');
        }

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }

        return $query->with('permissions')->latest()->paginate($perPage);
    }

    /**
     * Get all roles.
     */
    public function all(array $filters = [], ?User $currentUser = null): Collection
    {
        $query = Role::query();

        // If current user is not Super Admin, exclude Super Admin role
        if ($currentUser && !$currentUser->isSuperAdmin()) {
            $query->where('name', '!=', 'Super Admin');
        }

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }

        return $query->with('permissions')->latest()->get();
    }

    /**
     * Find role by ID.
     */
    public function find(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    /**
     * Find role by name.
     */
    public function findByName(string $name, string $guardName = 'web'): ?Role
    {
        return Role::where('name', $name)
            ->where('guard_name', $guardName)
            ->with('permissions')
            ->first();
    }

    /**
     * Create a new role.
     */
    public function create(array $data): Role
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        // Sync permissions if provided
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $this->syncPermissions($role, $data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Update role.
     */
    public function update(Role $role, array $data): Role
    {
        $role->update([
            'name' => $data['name'] ?? $role->name,
            'guard_name' => $data['guard_name'] ?? $role->guard_name,
        ]);

        // Sync permissions if provided
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $this->syncPermissions($role, $data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Delete role.
     */
    public function delete(Role $role): bool
    {
        return $role->delete();
    }

    /**
     * Sync permissions to role.
     */
    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        $role->syncPermissions($permissionIds);
        return $role->load('permissions');
    }

    /**
     * Give permission to role.
     */
    public function givePermissionTo(Role $role, int $permissionId): Role
    {
        $permission = \App\Models\V1\Auth\Permission::find($permissionId);
        if ($permission) {
            $role->givePermissionTo($permission);
        }
        return $role->load('permissions');
    }

    /**
     * Revoke permission from role.
     */
    public function revokePermissionTo(Role $role, int $permissionId): Role
    {
        $permission = \App\Models\V1\Auth\Permission::find($permissionId);
        if ($permission) {
            $role->revokePermissionTo($permission);
        }
        return $role->load('permissions');
    }
}

