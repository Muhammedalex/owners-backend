<?php

namespace App\Models\V1\Ownership;

use App\Traits\V1\Auth\HasUuid;
use App\Traits\V1\Media\HasMedia;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use HasFactory, HasUuid, HasMedia, HasDocuments;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'buildings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'portfolio_id',
        'ownership_id',
        'parent_id',
        'name',
        'code',
        'type',
        'description',
        'street',
        'city',
        'state',
        'country',
        'zip_code',
        'latitude',
        'longitude',
        'floors',
        'year',
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
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'floors' => 'integer',
            'year' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the portfolio that owns this building.
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class, 'portfolio_id');
    }

    /**
     * Get the ownership that owns this building.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the parent building.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'parent_id');
    }

    /**
     * Get the child buildings.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Building::class, 'parent_id');
    }

    /**
     * Get the floors in this building.
     */
    public function buildingFloors(): HasMany
    {
        return $this->hasMany(BuildingFloor::class, 'building_id');
    }

    /**
     * Scope a query to only include active buildings.
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
     * Scope a query to filter by ownership.
     */
    public function scopeForOwnership($query, int $ownershipId)
    {
        return $query->where('ownership_id', $ownershipId);
    }

    /**
     * Scope a query to filter by portfolio.
     */
    public function scopeForPortfolio($query, int $portfolioId)
    {
        return $query->where('portfolio_id', $portfolioId);
    }

    /**
     * Check if building is active.
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Activate the building.
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Deactivate the building.
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }
}
