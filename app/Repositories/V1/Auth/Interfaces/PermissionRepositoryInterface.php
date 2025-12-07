<?php

namespace App\Repositories\V1\Auth\Interfaces;

use App\Models\V1\Auth\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PermissionRepositoryInterface
{
    /**
     * Get all permissions with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all permissions.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find permission by ID.
     */
    public function find(int $id): ?Permission;

    /**
     * Find permission by name.
     */
    public function findByName(string $name, string $guardName = 'web'): ?Permission;

    /**
     * Get permissions grouped by module.
     */
    public function getGroupedByModule(): array;

    // Note: create, update, delete methods removed
    // Permissions are hard-coded in seeders and cannot be modified via API
}

