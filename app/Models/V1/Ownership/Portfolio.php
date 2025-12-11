<?php

namespace App\Models\V1\Ownership;

use App\Traits\V1\Auth\HasUuid;
use App\Traits\V1\Media\HasMedia;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    use HasFactory, HasUuid, HasMedia, HasDocuments;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'portfolios';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'ownership_id',
        'parent_id',
        'name',
        'code',
        'type',
        'description',
        'area',
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
            'active' => 'boolean',
        ];
    }

    /**
     * Get the ownership that owns this portfolio.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the parent portfolio.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class, 'parent_id');
    }

    /**
     * Get the child portfolios.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'parent_id');
    }

    /**
     * Get the locations for this portfolio.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(PortfolioLocation::class, 'portfolio_id');
    }

    /**
     * Get the buildings in this portfolio.
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'portfolio_id');
    }

    /**
     * Scope a query to only include active portfolios.
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
     * Check if portfolio is active.
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Activate the portfolio.
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Deactivate the portfolio.
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }
}
