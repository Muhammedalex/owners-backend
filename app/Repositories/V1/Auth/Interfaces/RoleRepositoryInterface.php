<?php

namespace App\Repositories\V1\Auth\Interfaces;

use App\Models\V1\Auth\Role;
use App\Models\V1\Auth\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface RoleRepositoryInterface
{
    /**
     * Get all roles with pagination.
     * 
     * @param int $perPage
     * @param array $filters
     * @param User|null $currentUser Current authenticated user (for filtering super admin role)
     */
    public function paginate(int $perPage = 15, array $filters = [], ?User $currentUser = null): LengthAwarePaginator;

    /**
     * Get all roles.
     * 
     * @param array $filters
     * @param User|null $currentUser Current authenticated user (for filtering super admin role)
     */
    public function all(array $filters = [], ?User $currentUser = null): Collection;

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

