<?php

namespace App\Services\V1\Auth;

use App\Models\V1\Auth\Role;
use App\Repositories\V1\Auth\Interfaces\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository
    ) {}

    /**
     * Get all roles with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->roleRepository->paginate($perPage, $filters);
    }

    /**
     * Get all roles.
     */
    public function all(array $filters = []): Collection
    {
        return $this->roleRepository->all($filters);
    }

    /**
     * Find role by ID.
     */
    public function find(int $id): ?Role
    {
        return $this->roleRepository->find($id);
    }

    /**
     * Find role by name.
     */
    public function findByName(string $name, string $guardName = 'web'): ?Role
    {
        return $this->roleRepository->findByName($name, $guardName);
    }

    /**
     * Create a new role.
     */
    public function create(array $data): Role
    {
        return $this->roleRepository->create($data);
    }

    /**
     * Update role.
     */
    public function update(Role $role, array $data): Role
    {
        return $this->roleRepository->update($role, $data);
    }

    /**
     * Delete role.
     */
    public function delete(Role $role): bool
    {
        return $this->roleRepository->delete($role);
    }

    /**
     * Sync permissions to role.
     */
    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        return $this->roleRepository->syncPermissions($role, $permissionIds);
    }

    /**
     * Give permission to role.
     */
    public function givePermissionTo(Role $role, int $permissionId): Role
    {
        return $this->roleRepository->givePermissionTo($role, $permissionId);
    }

    /**
     * Revoke permission from role.
     */
    public function revokePermissionTo(Role $role, int $permissionId): Role
    {
        return $this->roleRepository->revokePermissionTo($role, $permissionId);
    }
}

