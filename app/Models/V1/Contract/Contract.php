<?php

namespace App\Models\V1\Contract;

use App\Models\V1\Auth\User;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Ownership\Ownership;
use App\Models\V1\Ownership\Unit;
use App\Models\V1\Tenant\Tenant;
use App\Traits\V1\Auth\HasUuid;
use App\Traits\V1\Media\HasMedia;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory, HasUuid, HasMedia, HasDocuments;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contracts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'unit_id',
        'tenant_id',
        'ownership_id',
        'number',
        'version',
        'parent_id',
        'ejar_code',
        'start',
        'end',
        'rent',
        'payment_frequency',
        'deposit',
        'deposit_status',
        'document',
        'signature',
        'status',
        'created_by',
        'approved_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start' => 'date',
            'end' => 'date',
            'rent' => 'decimal:2',
            'deposit' => 'decimal:2',
            'version' => 'integer',
        ];
    }

    /**
     * Get the unit associated with this contract (legacy single-unit relation).
     *
     * NOTE: For new features, prefer using units()/primaryUnit().
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the units for this contract (many-to-many via contract_units).
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'contract_units', 'contract_id', 'unit_id')
            ->withPivot('rent_amount', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the primary unit for this contract (for backward compatibility).
     * Returns the first related unit (from pivot) or the legacy unit relation.
     */
    public function primaryUnit(): ?Unit
    {
        if ($this->relationLoaded('units') && $this->units->isNotEmpty()) {
            return $this->units->first();
        }

        $unit = $this->units()->first();

        if ($unit) {
            return $unit;
        }

        return $this->unit; // fallback to legacy relation
    }

    /**
     * Get the tenant associated with this contract.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the ownership associated with this contract.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the parent contract (for contract versions/renewals).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'parent_id');
    }

    /**
     * Get the child contracts (contract versions/renewals).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Contract::class, 'parent_id');
    }

    /**
     * Get the contract terms.
     */
    public function terms(): HasMany
    {
        return $this->hasMany(ContractTerm::class, 'contract_id');
    }

    /**
     * Get the invoices for this contract.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'contract_id');
    }

    /**
     * Get the user who created this contract.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this contract.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeWithStatus($query, string $status)
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
     * Scope a query to filter by tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to filter by unit.
     */
    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    /**
     * Check if contract has multiple units.
     */
    public function hasMultipleUnits(): bool
    {
        return $this->units()->count() > 1;
    }

    /**
     * Get total units count.
     */
    public function getUnitsCountAttribute(): int
    {
        return $this->units()->count();
    }

    /**
     * Scope a query to filter contracts by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start', [$startDate, $endDate])
                ->orWhereBetween('end', [$startDate, $endDate])
                ->orWhere(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('start', '<=', $startDate)
                        ->where('end', '>=', $endDate);
                });
        });
    }

    /**
     * Check if contract is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->start->isPast() && 
               $this->end->isFuture();
    }

    /**
     * Check if contract is expired.
     */
    public function isExpired(): bool
    {
        return $this->end->isPast();
    }

    /**
     * Check if contract is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if contract has ejar code.
     */
    public function hasEjarCode(): bool
    {
        return !empty($this->ejar_code);
    }
}

