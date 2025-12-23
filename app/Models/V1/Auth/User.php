<?php

namespace App\Models\V1\Auth;

use App\Models\V1\Ownership\Ownership;
use App\Models\V1\Ownership\OwnershipBoardMember;
use App\Models\V1\Ownership\UserOwnershipMapping;
use App\Notifications\V1\Auth\VerifyEmail;
use App\Traits\V1\Auth\GeneratesTokens;
use App\Traits\V1\Auth\HasUuid;
use App\Traits\V1\Auth\LogsActivity;
use App\Traits\V1\Media\HasMedia;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method array generateTokens(?string $deviceName = null)
 * @method array|null refreshAccessToken(string $refreshToken)
 * @method void revokeAllTokens()
 * @method bool revokeTokenByRefreshToken(string $refreshToken)
 * @method \Laravel\Sanctum\PersonalAccessToken|null currentAccessToken()
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasUuid, GeneratesTokens, LogsActivity, HasMedia, HasDocuments;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'type',
        'email',
        'phone',
        'phone_verified_at',
        'password',
        'first',
        'last',
        'company',
        'avatar',
        'active',
        'last_login_at',
        'attempts',
        'timezone',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'attempts' => 'integer',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first} {$this->last}") ?: $this->email;
    }

    /**
     * Scope a query to only include active users.
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
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Check if user is verified.
     */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Check if phone is verified.
     */
    public function isPhoneVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * Increment login attempts.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Reset login attempts.
     */
    public function resetAttempts(): void
    {
        $this->update(['attempts' => 0]);
    }

    /**
     * Record successful login.
     */
    public function recordLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'attempts' => 0,
        ]);
    }

    /**
     * Check if user is Super Admin.
     * Uses withSystemRoles() to bypass the global scope that excludes system roles.
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles()
            ->withoutGlobalScope(\App\Models\Scopes\ExcludeSystemRolesScope::class)
            ->where('name', 'Super Admin')
            ->exists();
    }

    /**
     * Get ownerships created by this user.
     */
    public function ownedOwnerships(): HasMany
    {
        return $this->hasMany(Ownership::class, 'created_by');
    }

    /**
     * Get ownerships mapped to this user.
     */
    public function ownerships(): BelongsToMany
    {
        return $this->belongsToMany(Ownership::class, 'user_ownership_mapping', 'user_id', 'ownership_id')
            ->withPivot('default', 'created_at');
    }

    /**
     * Get user ownership mappings.
     */
    public function ownershipMappings(): HasMany
    {
        return $this->hasMany(UserOwnershipMapping::class, 'user_id');
    }

    /**
     * Get board memberships.
     */
    public function boardMemberships(): HasMany
    {
        return $this->hasMany(OwnershipBoardMember::class, 'user_id');
    }

    /**
     * Get default ownership for this user.
     */
    public function getDefaultOwnership(): ?Ownership
    {
        // First, try to find mapping with default flag set to true
        // Load ownership relationship explicitly to avoid lazy loading issues
        $mapping = $this->ownershipMappings()
            ->with('ownership')
            ->where('default', true)
            ->first();

        // If no default mapping found, get the first mapping
        if (!$mapping) {
            $mapping = $this->ownershipMappings()
                ->with('ownership')
                ->first();
        }

        // Log for debugging
        if (app()->environment(['local', 'testing'])) {
            Log::info('getDefaultOwnership debug', [
                'user_id' => $this->id,
                'mapping_exists' => $mapping !== null,
                'mapping_id' => $mapping?->id,
                'ownership_id' => $mapping?->ownership_id,
                'is_default' => $mapping?->default,
                'ownership_loaded' => $mapping && $mapping->relationLoaded('ownership'),
                'ownership_id_from_relation' => $mapping?->ownership?->id,
            ]);
        }

        return $mapping?->ownership;
    }

    /**
     * Check if user has access to a specific ownership.
     */
    public function hasOwnership(int $ownershipId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->ownershipMappings()
            ->where('ownership_id', $ownershipId)
            ->exists();
    }

    /**
     * Check if user has access to a specific ownership by UUID.
     */
    public function hasOwnershipByUuid(string $ownershipUuid): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $ownership = Ownership::where('uuid', $ownershipUuid)->first();

        if (!$ownership) {
            return false;
        }

        return $this->hasOwnership($ownership->id);
    }

    /**
     * Get all ownership IDs that user has access to.
     */
    public function getOwnershipIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Ownership::pluck('id')->toArray();
        }

        return $this->ownershipMappings()
            ->pluck('ownership_id')
            ->toArray();
    }

    /**
     * Check if user is a collector.
     * Uses the Collector role from Spatie Permission.
     */
    public function isCollector(): bool
    {
        return $this->hasRole('Collector');
    }

    /**
     * Get assigned tenants for collector.
     */
    public function assignedTenants(int $ownershipId): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\V1\Tenant\Tenant::class,
            'collector_tenant_assignments',
            'collector_id',
            'tenant_id'
        )
        ->wherePivot('ownership_id', $ownershipId)
        ->wherePivot('is_active', true)
        ->withPivot('assigned_at', 'notes')
        ->withTimestamps();
    }

    /**
     * Get all collector assignments.
     */
    public function collectorAssignments(): HasMany
    {
        return $this->hasMany(\App\Models\V1\Invoice\CollectorTenantAssignment::class, 'collector_id');
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        // Only send if email verification is enabled
        if (config('auth.verification.enabled', false)) {
            $this->notify(new VerifyEmail);
        }
    }
}

