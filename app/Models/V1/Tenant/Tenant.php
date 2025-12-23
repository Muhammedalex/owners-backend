<?php

namespace App\Models\V1\Tenant;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Models\V1\Contract\Contract;
use App\Traits\V1\Media\HasMedia;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Tenant extends Model
{
    use HasFactory, HasMedia, HasDocuments;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ownership_id',
        'invitation_id',
        'national_id',
        'id_type',
        'id_document',
        'id_expiry',
        'commercial_registration_number',
        'commercial_registration_expiry',
        'commercial_owner_name',
        'municipality_license_number',
        'activity_name',
        'activity_type',
        'emergency_name',
        'emergency_phone',
        'emergency_relation',
        'employment',
        'employer',
        'income',
        'rating',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id_expiry' => 'date',
            'commercial_registration_expiry' => 'date',
            'income' => 'decimal:2',
        ];
    }

    /**
     * Get the user associated with this tenant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the ownership associated with this tenant.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the contracts for this tenant.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'tenant_id');
    }

    /**
     * Get the invitation that created this tenant (if created via invitation).
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(TenantInvitation::class, 'invitation_id');
    }

    /**
     * Scope a query to only include tenants for a specific ownership.
     */
    public function scopeForOwnership($query, int $ownershipId)
    {
        return $query->where('ownership_id', $ownershipId);
    }

    /**
     * Scope a query to filter by rating.
     */
    public function scopeWithRating($query, string $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope a query to filter by employment status.
     */
    public function scopeWithEmployment($query, string $employment)
    {
        return $query->where('employment', $employment);
    }

    /**
     * Scope a query to filter tenants visible to a collector.
     * Collectors can only see tenants they are assigned to.
     * If no tenants assigned, collector sees all tenants.
     */
    public function scopeForCollector($query, User $collector, int $ownershipId)
    {
        $invoiceSettings = app(\App\Services\V1\Invoice\InvoiceSettingService::class);
        
        // Check if collector system is enabled
        if (!$invoiceSettings->isCollectorSystemEnabled($ownershipId)) {
            return $query->whereRaw('1 = 0'); // Return empty if disabled
        }
        
        // Get assigned tenant IDs
        $tenantIds = $collector->assignedTenants($ownershipId)->select('tenants.id')->pluck('id');
        
        // If no tenants assigned, show all tenants (fallback behavior)
        if ($tenantIds->isEmpty()) {
            return $query->where('ownership_id', $ownershipId);
        }
        
        // Filter tenants for assigned tenants only
        return $query->where('ownership_id', $ownershipId)
            ->whereIn('id', $tenantIds);
    }

    /**
     * Check if tenant has valid ID document.
     */
    public function hasValidId(): bool
    {
        if (!$this->id_expiry) {
            return false;
        }

        return $this->id_expiry->isFuture();
    }

    /**
     * Check if tenant ID is expired.
     */
    public function isIdExpired(): bool
    {
        if (!$this->id_expiry) {
            return false;
        }

        return $this->id_expiry->isPast();
    }
}

