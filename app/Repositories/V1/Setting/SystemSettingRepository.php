<?php

namespace App\Repositories\V1\Setting;

use App\Models\V1\Setting\SystemSetting;
use App\Repositories\V1\Setting\Interfaces\SystemSettingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SystemSettingRepository implements SystemSettingRepositoryInterface
{
    /**
     * Cache duration in minutes.
     */
    private const CACHE_DURATION = 60;

    /**
     * Get all settings with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = SystemSetting::query();

        // Apply filters
        if (isset($filters['ownership_id'])) {
            if ($filters['ownership_id'] === 'system') {
                $query->systemWide();
            } else {
                $query->forOwnership($filters['ownership_id']);
            }
        }

        if (isset($filters['group'])) {
            $query->byGroup($filters['group']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['value_type'])) {
            $query->where('value_type', $filters['value_type']);
        }

        return $query->with('ownership')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all settings.
     */
    public function all(array $filters = []): Collection
    {
        $query = SystemSetting::query();

        // Apply filters (same as paginate)
        if (isset($filters['ownership_id'])) {
            if ($filters['ownership_id'] === 'system') {
                $query->systemWide();
            } else {
                $query->forOwnership($filters['ownership_id']);
            }
        }

        if (isset($filters['group'])) {
            $query->byGroup($filters['group']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['value_type'])) {
            $query->where('value_type', $filters['value_type']);
        }

        return $query->with('ownership')
            ->latest()
            ->get();
    }

    /**
     * Find setting by ID.
     */
    public function find(int $id): ?SystemSetting
    {
        return SystemSetting::with('ownership')->find($id);
    }

    /**
     * Find setting by key and ownership ID.
     */
    public function findByKey(string $key, ?int $ownershipId = null): ?SystemSetting
    {
        $cacheKey = $ownershipId === null
            ? "setting_system_{$key}"
            : "setting_{$ownershipId}_{$key}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($key, $ownershipId) {
            return SystemSetting::where('key', $key)
                ->where('ownership_id', $ownershipId)
                ->first();
        });
    }

    /**
     * Get settings by group.
     */
    public function getByGroup(string $group, ?int $ownershipId = null): Collection
    {
        $cacheKey = $ownershipId === null
            ? "settings_system_{$group}"
            : "settings_{$ownershipId}_{$group}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($group, $ownershipId) {
            return SystemSetting::byGroup($group)
                ->where('ownership_id', $ownershipId)
                ->with('ownership')
                ->get();
        });
    }

    /**
     * Get all settings for an ownership (including system defaults).
     */
    public function getAllForOwnership(int $ownershipId): Collection
    {
        $cacheKey = "settings_all_{$ownershipId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId) {
            // Get ownership-specific settings
            $ownershipSettings = SystemSetting::forOwnership($ownershipId)
                ->with('ownership')
                ->get()
                ->keyBy('key');

            // Get system-wide settings
            $systemSettings = SystemSetting::systemWide()
                ->with('ownership')
                ->get()
                ->keyBy('key');

            // Merge: ownership settings override system settings
            return $systemSettings->merge($ownershipSettings)->values();
        });
    }

    /**
     * Create a new setting.
     */
    public function create(array $data): SystemSetting
    {
        $setting = SystemSetting::create($data);
        
        // Clear related caches
        $this->clearRelatedCaches($setting);
        
        return $setting->load('ownership');
    }

    /**
     * Update setting.
     */
    public function update(SystemSetting $setting, array $data): SystemSetting
    {
        $setting->update($data);
        
        // Clear related caches
        $this->clearRelatedCaches($setting);
        
        return $setting->fresh(['ownership']);
    }

    /**
     * Delete setting.
     */
    public function delete(SystemSetting $setting): bool
    {
        // Clear related caches before deletion
        $this->clearRelatedCaches($setting);
        
        return $setting->delete();
    }

    /**
     * Get setting value with fallback.
     */
    public function getValue(string $key, ?int $ownershipId = null, $default = null)
    {
        // First try to get ownership-specific setting
        if ($ownershipId !== null) {
            $setting = $this->findByKey($key, $ownershipId);
            if ($setting) {
                return $setting->getTypedValue();
            }
        }

        // Fallback to system-wide setting
        $systemSetting = $this->findByKey($key, null);
        if ($systemSetting) {
            return $systemSetting->getTypedValue();
        }

        return $default;
    }

    /**
     * Set setting value.
     */
    public function setValue(string $key, $value, string $valueType, string $group, ?int $ownershipId = null, ?string $description = null): SystemSetting
    {
        $setting = $this->findByKey($key, $ownershipId);

        if ($setting) {
            // Update existing setting
            $setting->setTypedValue($value);
            $setting->value_type = $valueType;
            $setting->group = $group;
            if ($description !== null) {
                $setting->description = $description;
            }
            $setting->save();
        } else {
            // Create new setting
            $setting = SystemSetting::create([
                'ownership_id' => $ownershipId,
                'key' => $key,
                'value_type' => $valueType,
                'group' => $group,
                'description' => $description,
            ]);
            $setting->setTypedValue($value);
            $setting->save();
        }

        // Clear related caches
        $this->clearRelatedCaches($setting);

        return $setting->load('ownership');
    }

    /**
     * Clear related caches for a setting.
     */
    private function clearRelatedCaches(SystemSetting $setting): void
    {
        $ownershipId = $setting->ownership_id;

        // Clear key-specific cache
        $keyCache = $ownershipId === null
            ? "setting_system_{$setting->key}"
            : "setting_{$ownershipId}_{$setting->key}";
        Cache::forget($keyCache);

        // Clear group cache
        $groupCache = $ownershipId === null
            ? "settings_system_{$setting->group}"
            : "settings_{$ownershipId}_{$setting->group}";
        Cache::forget($groupCache);

        // Clear all settings cache for ownership
        if ($ownershipId !== null) {
            Cache::forget("settings_all_{$ownershipId}");
        }
    }
}

