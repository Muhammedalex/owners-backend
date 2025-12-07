<?php

namespace App\Repositories\V1\Auth\Interfaces;

use App\Models\V1\Auth\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface RoleRepositoryInterface
{
    /**
     * Get all roles with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all roles.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find role by ID.
     */
    public function find(int $id): ?Role;

    /**
     * Find role by name.
     */
    public function findByName(string $name, string $guardName = 'web'): ?Role;

    /**
     * Create a new role.
     */
    public function create(array $data): Role;

    /**
     * Update role.
     */
    public function update(Role $role, array $data): Role;

    /**
     * Delete role.
     */
    public function delete(Role $role): bool;

    /**
     * Sync permissions to role.
     */
    public function syncPermissions(Role $role, array $permissionIds): Role;

    /**
     * Give permission to role.
     */
    public function givePermissionTo(Role $role, int $permissionId): Role;

    /**
     * Revoke permission from role.
     */
    public function revokePermissionTo(Role $role, int $permissionId): Role;
}

