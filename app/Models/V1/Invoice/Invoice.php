<?php

namespace App\Models\V1\Invoice;

use App\Enums\V1\Invoice\InvoiceStatus;
use App\Models\V1\Auth\User;
use App\Models\V1\Contract\Contract;
use App\Models\V1\Ownership\Ownership;
use App\Traits\V1\Auth\HasUuid;
use App\Traits\V1\Document\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

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
        'tax_from_contract',
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
            'tax_from_contract' => 'boolean',
            'total' => 'decimal:2',
            'status' => InvoiceStatus::class,
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
     * Get the status logs for this invoice.
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(InvoiceStatusLog::class, 'invoice_id');
    }

    /**
     * Get the change logs for this invoice.
     */
    public function changeLogs(): HasMany
    {
        return $this->hasMany(InvoiceChangeLog::class, 'invoice_id');
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
    public function scopeWithStatus($query, string|InvoiceStatus $status)
    {
        $statusValue = $status instanceof InvoiceStatus ? $status->value : $status;
        return $query->where('status', $statusValue);
    }

    /**
     * Scope a query to filter by ownership.
     */
    public function scopeForOwnership($query, int $ownershipId)
    {
        return $query->where('ownership_id', $ownershipId);
    }

    /**
     * Scope a query to filter invoices visible to a collector.
     * Collectors can only see invoices for their assigned tenants.
     * If no tenants assigned, collector sees all invoices.
     */
    public function scopeForCollector($query, User $collector, int $ownershipId)
    {
        $invoiceSettings = app(\App\Services\V1\Invoice\InvoiceSettingService::class);
        
        // Check if collector system is enabled
        if (!$invoiceSettings->isCollectorSystemEnabled($ownershipId)) {
            return $query->whereRaw('1 = 0'); // Return empty if disabled
        }
        
        // If collector can see all tenants, return all invoices
        if ($invoiceSettings->collectorsCanSeeAllTenants($ownershipId)) {
            return $query->where('ownership_id', $ownershipId);
        }
        
        // Get assigned tenant IDs
        $tenantIds = $collector->assignedTenants($ownershipId)->select('tenants.id')->pluck('id');
        
        // If no tenants assigned, show all invoices (fallback behavior)
        if ($tenantIds->isEmpty()) {
            return $query->where('ownership_id', $ownershipId);
        }
        Log::info('tenantIds', ['tenantIds' => $tenantIds]);
        // Filter invoices linked to contracts with assigned tenants
        // Standalone invoices (contract_id = null) are not visible to collectors
        return $query->where('ownership_id', $ownershipId)
            ->whereHas('contract', function ($q) use ($tenantIds) {
                $q->whereIn('tenant_id', $tenantIds);
            });
    }

    /**
     * Scope a query to filter overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', InvoiceStatus::PAID->value)
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
        return $this->status === InvoiceStatus::PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::OVERDUE 
            || ($this->due->isPast() && !in_array($this->status, [InvoiceStatus::PAID, InvoiceStatus::CANCELLED]));
    }

    /**
     * Check if invoice is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::DRAFT;
    }

    /**
     * Check if invoice can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status->allowsEditing();
    }

    /**
     * Check if invoice can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return $this->status->allowsDeletion();
    }

    /**
     * Check if status transition is allowed.
     * 
     * Takes into account:
     * - Basic enum rules (allowedNextStatuses)
     * - Status workflow mode (strict/flexible)
     * - User permissions (if provided)
     * - Settings (if user provided)
     * 
     * @param InvoiceStatus $newStatus
     * @param User|null $user Optional user to check permissions
     * @return bool
     */
    public function canTransitionTo(InvoiceStatus $newStatus, ?User $user = null): bool
    {
        // If new status is final and already final, no transition allowed
        if ($newStatus->isFinal() && $this->status->isFinal()) {
            return false;
        }
        
        // Check basic enum rules first
        $basicAllowed = $this->status->canTransitionTo($newStatus);
        
        // If basic transition is allowed, check workflow mode and permissions
        if ($basicAllowed) {
            // In strict mode, only allow basic transitions
            // In flexible mode, allow all transitions (except final status restrictions)
            $invoiceSettingService = app(\App\Services\V1\Invoice\InvoiceSettingService::class);
            $workflowMode = $invoiceSettingService->getStatusWorkflow($this->ownership_id);
            
            if ($workflowMode === 'flexible') {
                // Flexible mode: allow all transitions except:
                // - Cannot transition to/from final statuses (unless basic rules allow)
                // - Cannot transition from CANCELLED or REFUNDED
                if ($this->status === InvoiceStatus::CANCELLED || $this->status === InvoiceStatus::REFUNDED) {
                    return false; // Cannot transition from final statuses
                }
                if ($newStatus === InvoiceStatus::CANCELLED || $newStatus === InvoiceStatus::REFUNDED) {
                    return true; // Can transition to final statuses in flexible mode
                }
                return true; // All other transitions allowed in flexible mode
            }
            
            // Strict mode: only allow basic transitions
            return true;
        }
        
        // If basic transition is NOT allowed, check if flexible mode or permissions allow it
        $invoiceSettingService = app(\App\Services\V1\Invoice\InvoiceSettingService::class);
        $workflowMode = $invoiceSettingService->getStatusWorkflow($this->ownership_id);
        
        // In flexible mode, allow backward transitions
        if ($workflowMode === 'flexible') {
            // Cannot transition from final statuses
            if ($this->status === InvoiceStatus::CANCELLED || $this->status === InvoiceStatus::REFUNDED) {
                return false;
            }
            
            // Cannot transition to final statuses without basic rules (except REFUNDED from PAID)
            if ($newStatus === InvoiceStatus::CANCELLED || $newStatus === InvoiceStatus::REFUNDED) {
                // REFUNDED can only come from PAID
                if ($newStatus === InvoiceStatus::REFUNDED && $this->status === InvoiceStatus::PAID) {
                    return true;
                }
                // CANCELLED can come from any non-final status in flexible mode
                if ($newStatus === InvoiceStatus::CANCELLED) {
                    return true;
                }
            }
            
            // For backward transitions (e.g., SENT -> PENDING, VIEWED -> SENT), check permissions if user provided
            if (in_array($this->status, [InvoiceStatus::SENT, InvoiceStatus::VIEWED, InvoiceStatus::OVERDUE])) {
                // If user is provided, check permissions
                if ($user) {
                    // Check if user has editSent permission
                    if (!$user->can('invoices.editSent')) {
                        return false;
                    }
                    // Check settings
                    if (!$invoiceSettingService->canEditSent($this->ownership_id)) {
                        return false;
                    }
                }
                // Allow backward transitions in flexible mode (with permissions if user provided)
                return true;
            }
            
            // For other backward transitions (e.g., PARTIAL -> SENT, DRAFT -> SENT), check if user can edit (if user provided)
            if ($user) {
                $editRulesService = app(\App\Services\V1\Invoice\InvoiceEditRulesService::class);
                if (!$editRulesService->canEdit($this, $user)) {
                    return false;
                }
            }
            
            // Allow all other transitions in flexible mode
            return true;
        }
        
        return false;
    }

    /**
     * Transition to new status.
     */
    public function transitionTo(InvoiceStatus $newStatus, ?string $reason = null, ?int $changedBy = null): void
    {
        // Get current user if available for permission checks
        $user = $changedBy ? \App\Models\V1\Auth\User::find($changedBy) : (\Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user() : null);
        
        // Use canTransitionTo with user context for permission checks
        if (!$this->canTransitionTo($newStatus, $user)) {
            throw new \Exception("Cannot transition from {$this->status->value} to {$newStatus->value}");
        }
        
        $oldStatus = $this->status;
        $this->status = $newStatus;
        
        // Update related timestamps
        if ($newStatus === InvoiceStatus::PAID) {
            $this->paid_at = now();
        }
        
        $this->save();
        
        // Log status change
        $this->logStatusChange($oldStatus, $newStatus, $reason, $changedBy);
    }

    /**
     * Log status change.
     */
    protected function logStatusChange(InvoiceStatus $oldStatus, InvoiceStatus $newStatus, ?string $reason = null, ?int $changedBy = null): void
    {
        \App\Models\V1\Invoice\InvoiceStatusLog::create([
            'invoice_id' => $this->getKey(),
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus->value,
            'reason' => $reason,
            'changed_by' => $changedBy ?? (\Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null),
            'changed_at' => now(),
        ]);
    }

    /**
     * Auto-update status based on payments.
     */
    public function updateStatusFromPayments(): void
    {
        $totalPaid = $this->payments()->where('status', 'paid')->sum('amount');
        
        // Don't update status if invoice is in final states
        if (in_array($this->status, [InvoiceStatus::CANCELLED, InvoiceStatus::REFUNDED])) {
            return;
        }
        
        // Get invoice settings
        $invoiceSettings = app(\App\Services\V1\Invoice\InvoiceSettingService::class);
        $autoMarkAsPaid = $invoiceSettings->autoMarkAsPaid($this->ownership_id);
        $allowPartialPayment = $invoiceSettings->allowPartialPayment($this->ownership_id);
        
        // Handle full payment
        if ($totalPaid >= $this->total) {
            if ($this->status !== InvoiceStatus::PAID) {
                // Check if auto-mark as paid is enabled
                if (!$autoMarkAsPaid) {
                    // If auto-mark is disabled, don't automatically transition to PAID
                    // User must manually mark as paid
                    return;
                }
                
                // Check if transition is allowed
                if ($this->canTransitionTo(InvoiceStatus::PAID)) {
                    $this->transitionTo(InvoiceStatus::PAID, 'Full payment received');
                } else {
                    // If can't transition directly, try to go through SENT first
                    if (in_array($this->status, [InvoiceStatus::DRAFT, InvoiceStatus::PENDING])) {
                        if ($this->canTransitionTo(InvoiceStatus::SENT)) {
                            $this->transitionTo(InvoiceStatus::SENT, 'Invoice sent before payment');
                            // Refresh to get updated status
                            $this->refresh();
                        }
                        // Then try PAID again (SENT can transition to PAID)
                        if ($this->status === InvoiceStatus::SENT && $this->canTransitionTo(InvoiceStatus::PAID)) {
                            $this->transitionTo(InvoiceStatus::PAID, 'Full payment received');
                        }
                    }
                }
            }
        } 
        // Handle partial payment
        elseif ($totalPaid > 0) {
            // Check if partial payments are allowed
            if (!$allowPartialPayment) {
                // If partial payments are not allowed, don't transition to PARTIAL
                // Keep current status or log warning
                Log::warning('Partial payment received but partial payments are not allowed', [
                    'invoice_id' => $this->id,
                    'total_paid' => $totalPaid,
                    'invoice_total' => $this->total,
                ]);
                return;
            }
            
            if ($this->status !== InvoiceStatus::PARTIAL) {
                // Check if transition is allowed
                if ($this->canTransitionTo(InvoiceStatus::PARTIAL)) {
                    $this->transitionTo(InvoiceStatus::PARTIAL, 'Partial payment received');
                } else {
                    // If can't transition directly, try to go through SENT first
                    if (in_array($this->status, [InvoiceStatus::DRAFT, InvoiceStatus::PENDING])) {
                        if ($this->canTransitionTo(InvoiceStatus::SENT)) {
                            $this->transitionTo(InvoiceStatus::SENT, 'Invoice sent before payment');
                            // Refresh to get updated status
                            $this->refresh();
                        }
                        // Then try PARTIAL again (SENT can transition to PARTIAL)
                        if ($this->status === InvoiceStatus::SENT && $this->canTransitionTo(InvoiceStatus::PARTIAL)) {
                            $this->transitionTo(InvoiceStatus::PARTIAL, 'Partial payment received');
                        }
                    }
                }
            }
        } 
        // Handle no payment (revert status)
        else {
            // If was PAID or PARTIAL and now no payments, revert to previous status
            if (in_array($this->status, [InvoiceStatus::PAID, InvoiceStatus::PARTIAL])) {
                // Try to find previous status from logs
                $previousStatus = $this->getPreviousStatusBeforePayment();
                if ($previousStatus && $this->canTransitionTo($previousStatus)) {
                    $this->transitionTo($previousStatus, 'All payments removed');
                } elseif ($this->canTransitionTo(InvoiceStatus::SENT)) {
                    // Default to SENT if can't find previous status
                    $this->transitionTo(InvoiceStatus::SENT, 'All payments removed');
                }
            }
            // Handle overdue - only from allowed statuses
            elseif ($this->due->isPast() && 
                    !in_array($this->status, [InvoiceStatus::PAID, InvoiceStatus::CANCELLED, InvoiceStatus::OVERDUE, InvoiceStatus::REFUNDED]) &&
                    $this->canTransitionTo(InvoiceStatus::OVERDUE)) {
                $this->transitionTo(InvoiceStatus::OVERDUE, 'Due date passed');
            }
        }
    }

    /**
     * Get previous status before payment status (PAID/PARTIAL).
     * 
     * @return InvoiceStatus|null
     */
    protected function getPreviousStatusBeforePayment(): ?InvoiceStatus
    {
        $statusLog = $this->statusLogs()
            ->whereIn('old_status', ['sent', 'viewed'])
            ->whereIn('new_status', ['partial', 'paid'])
            ->orderBy('changed_at', 'desc')
            ->first();
        
        if ($statusLog) {
            return InvoiceStatus::tryFrom($statusLog->old_status);
        }
        
        return null;
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->transitionTo(InvoiceStatus::SENT, 'Invoice sent to tenant');
    }

    /**
     * Mark as viewed.
     */
    public function markAsViewed(): void
    {
        if ($this->status === InvoiceStatus::SENT) {
            $this->transitionTo(InvoiceStatus::VIEWED, 'Invoice viewed by tenant');
        }
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

    /**
     * Check if invoice is linked to a contract.
     */
    public function isLinkedToContract(): bool
    {
        return !is_null($this->contract_id);
    }

    /**
     * Check if invoice is standalone (not linked to contract).
     */
    public function isStandalone(): bool
    {
        return is_null($this->contract_id);
    }

    /**
     * Scope a query to only include invoices linked to contracts.
     */
    public function scopeLinkedToContracts($query)
    {
        return $query->whereNotNull('contract_id');
    }

    /**
     * Scope a query to only include standalone invoices.
     */
    public function scopeStandalone($query)
    {
        return $query->whereNull('contract_id');
    }

    /**
     * Scope a query to filter by contract.
     */
    public function scopeForContract($query, ?int $contractId)
    {
        if ($contractId) {
            return $query->where('contract_id', $contractId);
        }
        return $query;
    }

    /**
     * Get invoice amount from contract if linked.
     */
    public function getAmountFromContract(): ?float
    {
        if (!$this->isLinkedToContract() || !$this->contract) {
            return null;
        }
        
        // حساب القيمة حسب payment_frequency
        return $this->calculateAmountByFrequency();
    }

    /**
     * Calculate amount based on contract payment frequency.
     */
    private function calculateAmountByFrequency(): float
    {
        $contract = $this->contract;
        $totalRent = $contract->total_rent;
        Log::info('totalRent', ['totalRent' => $totalRent]);
        switch ($contract->payment_frequency) {
            case 'monthly':
                return $totalRent / 12;
            case 'quarterly':
                return $totalRent / 4;
            case 'semi_annually':
                return $totalRent / 2;
            case 'yearly':
                return $totalRent;
            default:
                return $totalRent / 12; // default monthly
        }
    }

    /**
     * Calculate total (with or without tax).
     */
    public function calculateTotal(): float
    {
        if ($this->isLinkedToContract() && $this->tax_from_contract) {
            // القيمة من العقد (شاملة الضريبة)
            return $this->amount;
        }
        
        // فاتورة مستقلة - تحسب الضريبة
        if ($this->tax_rate && $this->tax_rate > 0) {
            return $this->amount + ($this->amount * $this->tax_rate / 100);
        }
        
        return $this->amount;
    }
}

