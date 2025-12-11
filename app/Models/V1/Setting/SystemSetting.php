<?php

namespace App\Models\V1\Setting;

use App\Models\V1\Ownership\Ownership;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ownership_id',
        'key',
        'value',
        'value_type',
        'group',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ownership_id' => 'integer',
        ];
    }

    /**
     * Get the ownership associated with this setting.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Scope a query to only include system-wide settings.
     */
    public function scopeSystemWide($query)
    {
        return $query->whereNull('ownership_id');
    }

    /**
     * Scope a query to only include ownership-specific settings.
     */
    public function scopeForOwnership($query, int $ownershipId)
    {
        return $query->where('ownership_id', $ownershipId);
    }

    /**
     * Scope a query to filter by group.
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope a query to filter by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Get the typed value based on value_type.
     */
    public function getTypedValue()
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->value_type) {
            'integer' => (int) $this->value,
            'decimal' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            'array' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set the value with proper type conversion.
     */
    public function setTypedValue($value): void
    {
        $this->value = match ($this->value_type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Check if this is a system-wide setting.
     */
    public function isSystemWide(): bool
    {
        return $this->ownership_id === null;
    }

    /**
     * Check if this is an ownership-specific setting.
     */
    public function isOwnershipSpecific(): bool
    {
        return $this->ownership_id !== null;
    }

    /**
     * Clear cache for this setting.
     */
    public function clearCache(): void
    {
        $cacheKey = $this->isSystemWide()
            ? "setting_system_{$this->key}"
            : "setting_{$this->ownership_id}_{$this->key}";
        
        Cache::forget($cacheKey);
        
        // Also clear group cache
        $groupCacheKey = $this->isSystemWide()
            ? "settings_system_{$this->group}"
            : "settings_{$this->ownership_id}_{$this->group}";
        
        Cache::forget($groupCacheKey);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when setting is saved or deleted
        static::saved(function ($setting) {
            $setting->clearCache();
        });

        static::deleted(function ($setting) {
            $setting->clearCache();
        });
    }
}

