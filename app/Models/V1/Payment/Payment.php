<?php

namespace App\Models\V1\Payment;

use App\Models\V1\Auth\User;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Ownership\Ownership;
use App\Traits\V1\Auth\HasUuid;
use App\Traits\V1\Media\HasMedia;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuid, HasMedia, HasDocuments;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'invoice_id',
        'ownership_id',
        'method',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'confirmed_by',
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
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the invoice associated with this payment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Get the ownership associated with this payment.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the user who confirmed this payment.
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
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
     * Scope a query to filter by invoice.
     */
    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope a query to filter by method.
     */
    public function scopeWithMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Scope a query to filter payments visible to a collector.
     * Collectors can only see payments for invoices linked to contracts with their assigned tenants.
     * If no tenants assigned, collector sees all payments.
     */
    public function scopeForCollector($query, User $collector, int $ownershipId)
    {
        $invoiceSettings = app(\App\Services\V1\Invoice\InvoiceSettingService::class);
        
        // Check if collector system is enabled
        if (!$invoiceSettings->isCollectorSystemEnabled($ownershipId)) {
            return $query->whereRaw('1 = 0'); // Return empty if disabled
        }
        
        // If collector can see all tenants, return all payments
        if ($invoiceSettings->collectorsCanSeeAllTenants($ownershipId)) {
            return $query->where('ownership_id', $ownershipId);
        }
        
        // Get assigned tenant IDs
        $tenantIds = $collector->assignedTenants($ownershipId)->select('tenants.id')->pluck('id');
        
        // If no tenants assigned, show all payments (fallback behavior)
        if ($tenantIds->isEmpty()) {
            return $query->where('ownership_id', $ownershipId);
        }
        
        // Filter payments linked to invoices with contracts that have assigned tenants
        // Standalone invoices (contract_id = null) are not visible to collectors
        return $query->where('ownership_id', $ownershipId)
            ->whereHas('invoice', function ($q) use ($tenantIds) {
                $q->whereHas('contract', function ($contractQuery) use ($tenantIds) {
                    $contractQuery->whereIn('tenant_id', $tenantIds);
                });
            });
    }

    /**
     * Check if payment is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is unpaid.
     */
    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }
}

