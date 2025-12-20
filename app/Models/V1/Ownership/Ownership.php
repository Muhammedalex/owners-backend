<?php

namespace App\Models\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Traits\V1\Auth\HasUuid;
use App\Traits\V1\Media\HasMedia;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ownership extends Model
{
    use HasFactory, HasUuid, HasMedia, HasDocuments;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ownerships';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'legal',
        'type',
        'ownership_type',
        'registration',
        'tax_id',
        'street',
        'city',
        'state',
        'country',
        'zip_code',
        'email',
        'phone',
        'active',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Get the user who created this ownership.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the board members for this ownership.
     */
    public function boardMembers(): HasMany
    {
        return $this->hasMany(OwnershipBoardMember::class, 'ownership_id');
    }

    /**
     * Get the user ownership mappings for this ownership.
     */
    public function userMappings(): HasMany
    {
        return $this->hasMany(UserOwnershipMapping::class, 'ownership_id');
    }

    /**
     * Get the users mapped to this ownership.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_ownership_mapping', 'ownership_id', 'user_id')
            ->withPivot('default')
            ->withTimestamps();
    }

    /**
     * Get the portfolios for this ownership.
     */
    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'ownership_id');
    }

    /**
     * Get the buildings for this ownership.
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'ownership_id');
    }

    /**
     * Get the units for this ownership.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'ownership_id');
    }

    /**
     * Get the settings for this ownership.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(\App\Models\V1\Setting\SystemSetting::class, 'ownership_id');
    }

    /**
     * Scope a query to only include active ownerships.
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
     * Scope a query to filter by ownership_type.
     */
    public function scopeOfOwnershipType($query, string $ownershipType)
    {
        return $query->where('ownership_type', $ownershipType);
    }

    /**
     * Scope a query to filter by city.
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Check if ownership is active.
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Activate the ownership.
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Deactivate the ownership.
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }
}
