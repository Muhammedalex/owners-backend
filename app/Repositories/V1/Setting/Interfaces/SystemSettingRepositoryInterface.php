<?php

namespace App\Repositories\V1\Setting\Interfaces;

use App\Models\V1\Setting\SystemSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SystemSettingRepositoryInterface
{
    /**
     * Get all settings with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all settings.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find setting by ID.
     */
    public function find(int $id): ?SystemSetting;

    /**
     * Find setting by key and ownership ID.
     */
    public function findByKey(string $key, ?int $ownershipId = null): ?SystemSetting;

    /**
     * Get settings by group.
     */
    public function getByGroup(string $group, ?int $ownershipId = null): Collection;

    /**
     * Get all settings for an ownership (including system defaults).
     */
    public function getAllForOwnership(int $ownershipId): Collection;

    /**
     * Create a new setting.
     */
    public function create(array $data): SystemSetting;

    /**
     * Update setting.
     */
    public function update(SystemSetting $setting, array $data): SystemSetting;

    /**
     * Delete setting.
     */
    public function delete(SystemSetting $setting): bool;

    /**
     * Get setting value with fallback.
     */
    public function getValue(string $key, ?int $ownershipId = null, $default = null);

    /**
     * Set setting value.
     */
    public function setValue(string $key, $value, string $valueType, string $group, ?int $ownershipId = null, ?string $description = null): SystemSetting;
}

