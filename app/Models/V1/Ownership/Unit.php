<?php

namespace App\Models\V1\Ownership;

use App\Traits\V1\Auth\HasUuid;
use App\Traits\V1\Media\HasMedia;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory, HasUuid, HasMedia, HasDocuments;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'units';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'building_id',
        'floor_id',
        'ownership_id',
        'number',
        'type',
        'name',
        'description',
        'area',
        'price_monthly',
        'price_quarterly',
        'price_yearly',
        'status',
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
            'area' => 'decimal:2',
            'price_monthly' => 'decimal:2',
            'price_quarterly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the building that owns this unit.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    /**
     * Get the floor that owns this unit.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(BuildingFloor::class, 'floor_id');
    }

    /**
     * Get the ownership that owns this unit.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the specifications for this unit.
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(UnitSpecification::class, 'unit_id');
    }

    /**
     * Scope a query to only include active units.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by ownership.
     */
    public function scopeForOwnership($query, int $ownershipId)
    {
        return $query->where('ownership_id', $ownershipId);
    }

    /**
     * Scope a query to filter by building.
     */
    public function scopeForBuilding($query, int $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    /**
     * Scope a query to filter by floor.
     */
    public function scopeForFloor($query, int $floorId)
    {
        return $query->where('floor_id', $floorId);
    }

    /**
     * Check if unit is active.
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Activate the unit.
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Deactivate the unit.
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Get the contracts for this unit (many-to-many via contract_units).
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\V1\Contract\Contract::class, 'contract_units', 'unit_id', 'contract_id')
            ->withPivot('rent_amount', 'notes')
            ->withTimestamps();
    }

    /**
     * Get active contract for this unit.
     */
    public function activeContract(): ?\App\Models\V1\Contract\Contract
    {
        return $this->contracts()
            ->where('status', 'active')
            ->where('start', '<=', now())
            ->where('end', '>=', now())
            ->first();
    }

    /**
     * Check if unit is available (status + no active contract).
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->activeContract() === null;
    }
}
