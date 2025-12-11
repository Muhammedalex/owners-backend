<?php

namespace App\Models\V1\Invoice;

use App\Models\V1\Auth\User;
use App\Models\V1\Contract\Contract;
use App\Models\V1\Ownership\Ownership;
use App\Traits\V1\Auth\HasUuid;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUuid, HasDocuments;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'contract_id',
        'ownership_id',
        'number',
        'period_start',
        'period_end',
        'due',
        'amount',
        'tax',
        'tax_rate',
        'total',
        'status',
        'notes',
        'generated_by',
        'generated_at',
        'paid_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due' => 'date',
            'amount' => 'decimal:2',
            'tax' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'total' => 'decimal:2',
            'generated_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the contract associated with this invoice.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * Get the ownership associated with this invoice.
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'ownership_id');
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    /**
     * Get the payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\V1\Payment\Payment::class, 'invoice_id');
    }

    /**
     * Get the user who generated this invoice.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
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
     * Scope a query to filter by contract.
     */
    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    /**
     * Scope a query to filter overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('due', '<', now());
    }

    /**
     * Scope a query to filter invoices by date range.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate])
            ->orWhereBetween('period_end', [$startDate, $endDate]);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due->isPast();
    }

    /**
     * Check if invoice is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Calculate total from items.
     */
    public function calculateTotalFromItems(): float
    {
        return $this->items->sum('total');
    }

    /**
     * Calculate tax amount.
     */
    public function calculateTax(): float
    {
        return $this->amount * ($this->tax_rate / 100);
    }

    /**
     * Calculate total with tax.
     */
    public function calculateTotalWithTax(): float
    {
        return $this->amount + $this->tax;
    }
}

