<?php

namespace App\Models\V1\Ownership;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingFloor extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'building_floors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'building_id',
        'number',
        'name',
        'description',
        'units',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'units' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the building that owns this floor.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    /**
     * Get the units on this floor.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'floor_id');
    }

    /**
     * Scope a query to only include active floors.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to filter by building.
     */
    public function scopeForBuilding($query, int $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    /**
     * Check if floor is active.
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Activate the floor.
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Deactivate the floor.
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }
}
