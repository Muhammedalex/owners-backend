<?php

namespace App\Models\V1\Ownership;

use App\Models\V1\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOwnershipMapping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_ownership_mapping';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ownership_id',
        'default',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user for this mapping.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the ownership for this mapping.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Scope a query to only include default mappings.
     */
    public function scopeDefault($query)
    {
        return $query->where('default', true);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by ownership.
     */
    public function scopeForOwnership($query, int $ownershipId)
    {
        return $query->where('ownership_id', $ownershipId);
    }

    /**
     * Check if this is the default ownership for the user.
     */
    public function isDefault(): bool
    {
        return $this->default === true;
    }

    /**
     * Set as default ownership.
     */
    public function setAsDefault(): void
    {
        // Remove default from other mappings for this user
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['default' => false]);

        // Set this as default
        $this->update(['default' => true]);
    }
}
