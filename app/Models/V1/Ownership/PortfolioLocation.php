<?php

namespace App\Models\V1\Ownership;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioLocation extends Model
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
    protected $table = 'portfolio_locations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'portfolio_id',
        'street',
        'city',
        'state',
        'country',
        'zip_code',
        'latitude',
        'longitude',
        'primary',
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
            'primary' => 'boolean',
        ];
    }

    /**
     * Get the portfolio that owns this location.
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class, 'portfolio_id');
    }

    /**
     * Scope a query to only include primary locations.
     */
    public function scopePrimary($query)
    {
        return $query->where('primary', true);
    }

    /**
     * Check if this is the primary location.
     */
    public function isPrimary(): bool
    {
        return $this->primary === true;
    }
}
