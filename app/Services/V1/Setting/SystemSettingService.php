<?php

namespace App\Services\V1\Setting;

use App\Models\V1\Setting\SystemSetting;
use App\Repositories\V1\Setting\Interfaces\SystemSettingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SystemSettingService
{
    public function __construct(
        private SystemSettingRepositoryInterface $settingRepository
    ) {}

    /**
     * Get all settings with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->settingRepository->paginate($perPage, $filters);
    }

    /**
     * Get all settings.
     */
    public function all(array $filters = []): Collection
    {
        return $this->settingRepository->all($filters);
    }

    /**
     * Find setting by ID.
     */
    public function find(int $id): ?SystemSetting
    {
        return $this->settingRepository->find($id);
    }

    /**
     * Find setting by key.
     */
    public function findByKey(string $key, ?int $ownershipId = null): ?SystemSetting
    {
        return $this->settingRepository->findByKey($key, $ownershipId);
    }

    /**
     * Get settings by group.
     */
    public function getByGroup(string $group, ?int $ownershipId = null): Collection
    {
        return $this->settingRepository->getByGroup($group, $ownershipId);
    }

    /**
     * Get all settings for ownership (with system defaults fallback).
     */
    public function getAllForOwnership(int $ownershipId): Collection
    {
        return $this->settingRepository->getAllForOwnership($ownershipId);
    }

    /**
     * Get setting value with fallback.
     */
    public function getValue(string $key, ?int $ownershipId = null, $default = null)
    {
        return $this->settingRepository->getValue($key, $ownershipId, $default);
    }

    /**
     * Create a new setting.
     */
    public function create(array $data): SystemSetting
    {
        return DB::transaction(function () use ($data) {
            // Convert value based on value_type
            $value = $data['value'] ?? null;
            $valueType = $data['value_type'] ?? 'string';
            
            return $this->settingRepository->setValue(
                $data['key'],
                $value,
                $valueType,
                $data['group'],
                $data['ownership_id'] ?? null,
                $data['description'] ?? null
            );
        });
    }

    /**
     * Update setting.
     */
    public function update(SystemSetting $setting, array $data): SystemSetting
    {
        return DB::transaction(function () use ($setting, $data) {
            // If value is provided, convert it based on value_type
            if (isset($data['value'])) {
                $valueType = $data['value_type'] ?? $setting->value_type;
                $setting->setTypedValue($data['value']);
                $setting->value_type = $valueType;
            }

            // Update other fields
            if (isset($data['group'])) {
                $setting->group = $data['group'];
            }

            if (isset($data['description'])) {
                $setting->description = $data['description'];
            }

            return $this->settingRepository->update($setting, $setting->getAttributes());
        });
    }

    /**
     * Delete setting.
     */
    public function delete(SystemSetting $setting): bool
    {
        return DB::transaction(function () use ($setting) {
            return $this->settingRepository->delete($setting);
        });
    }

    /**
     * Set setting value (create or update).
     */
    public function setValue(string $key, $value, string $valueType, string $group, ?int $ownershipId = null, ?string $description = null): SystemSetting
    {
        return DB::transaction(function () use ($key, $value, $valueType, $group, $ownershipId, $description) {
            return $this->settingRepository->setValue($key, $value, $valueType, $group, $ownershipId, $description);
        });
    }

    /**
     * Bulk update settings.
     */
    public function bulkUpdate(array $settings, ?int $ownershipId = null): Collection
    {
        return DB::transaction(function () use ($settings, $ownershipId) {
            $updated = collect();

            foreach ($settings as $settingData) {
                $key = $settingData['key'];
                $value = $settingData['value'] ?? null;
                $valueType = $settingData['value_type'] ?? 'string';
                $group = $settingData['group'];
                $description = $settingData['description'] ?? null;

                $setting = $this->settingRepository->setValue(
                    $key,
                    $value,
                    $valueType,
                    $group,
                    $ownershipId,
                    $description
                );

                $updated->push($setting);
            }

            return $updated;
        });
    }
}

