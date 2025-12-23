<?php

namespace App\Repositories\V1\Invoice;

use App\Models\V1\Invoice\Invoice;
use App\Repositories\V1\Invoice\Interfaces\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * Get all invoices with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::query();

        // Apply collector scope if collector_id is provided
        if (isset($filters['collector_id']) && isset($filters['collector_ownership_id'])) {
            $collector = \App\Models\V1\Auth\User::find($filters['collector_id']);
            if ($collector && $collector->isCollector()) {
                $query->forCollector($collector, $filters['collector_ownership_id']);
            }
            unset($filters['collector_id'], $filters['collector_ownership_id']);
        } else {
            // Apply ownership filter for regular users
            if (isset($filters['ownership_id'])) {
                $query->forOwnership($filters['ownership_id']);
            }
        }

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('contract', function ($contractQuery) use ($search) {
                        $contractQuery->where('number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('contract.tenant.user', function ($userQuery) use ($search) {
                        $userQuery->where('first', 'like', "%{$search}%")
                            ->orWhere('last', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['contract_id'])) {
            $query->forContract($filters['contract_id']);
        }

        // Filter by tenant ID
        if (isset($filters['tenant_id']) && $filters['tenant_id']) {
            $query->whereHas('contract', function ($q) use ($filters) {
                $q->where('tenant_id', $filters['tenant_id']);
            });
        }

        // Filter by unit ID
        if (isset($filters['unit_id']) && $filters['unit_id']) {
            $query->whereHas('contract.units', function ($q) use ($filters) {
                $q->where('units.id', $filters['unit_id']);
            });
        }

        // Filter standalone invoices
        if (isset($filters['standalone']) && $filters['standalone'] === true) {
            $query->standalone();
        }

        // Filter contract-linked invoices
        if (isset($filters['linked_to_contracts']) && $filters['linked_to_contracts'] === true) {
            $query->linkedToContracts();
        }

        // Filter overdue invoices (only if true)
        if (isset($filters['overdue']) && $filters['overdue'] === true) {
            $query->overdue();
        }

        // Filter by date range
        if (isset($filters['start_date']) || isset($filters['end_date'])) {
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                // Both dates provided - use inPeriod scope
                $query->inPeriod($filters['start_date'], $filters['end_date']);
            } elseif (isset($filters['start_date'])) {
                // Only start date - filter from this date onwards
                $query->where(function ($q) use ($filters) {
                    $q->where('period_start', '>=', $filters['start_date'])
                        ->orWhere('period_end', '>=', $filters['start_date']);
                });
            } elseif (isset($filters['end_date'])) {
                // Only end date - filter up to this date
                $query->where(function ($q) use ($filters) {
                    $q->where('period_start', '<=', $filters['end_date'])
                        ->orWhere('period_end', '<=', $filters['end_date']);
                });
            }
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        // Filter by tenant IDs (for collectors - invoices.viewOwn)
        if (isset($filters['tenant_ids']) && is_array($filters['tenant_ids']) && !empty($filters['tenant_ids'])) {
            $query->whereHas('contract', function ($q) use ($filters) {
                $q->whereIn('tenant_id', $filters['tenant_ids']);
            });
        }

        // Apply sorting
        if (isset($filters['sort'])) {
            $sortField = $filters['sort'];
            $sortOrder = isset($filters['order']) && strtolower($filters['order']) === 'desc' ? 'desc' : 'asc';
            
            // Map frontend field names to database columns
            $fieldMap = [
                'number' => 'number',
                'contract' => 'contract_id',
                'total' => 'total',
                'due' => 'due',
                'dueDate' => 'due',
                'status' => 'status',
                'period_start' => 'period_start',
                'period_end' => 'period_end',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ];
            
            $dbField = $fieldMap[$sortField] ?? $sortField;
            $query->orderBy($dbField, $sortOrder);
        } else {
            // Default sorting
            $query->latest();
        }

        return $query->with(['contract.tenant.user', 'ownership', 'generatedBy', 'items', 'payments'])
            ->paginate($perPage);
    }

    /**
     * Get all invoices.
     */
    public function all(array $filters = []): Collection
    {
        $query = Invoice::query();

        // Apply collector scope if collector_id is provided
        if (isset($filters['collector_id']) && isset($filters['collector_ownership_id'])) {
            $collector = \App\Models\V1\Auth\User::find($filters['collector_id']);
            if ($collector && $collector->isCollector()) {
                $query->forCollector($collector, $filters['collector_ownership_id']);
            }
            unset($filters['collector_id'], $filters['collector_ownership_id']);
        } else {
            // Apply ownership filter for regular users
            if (isset($filters['ownership_id'])) {
                $query->forOwnership($filters['ownership_id']);
            }
        }

        // Apply filters (same as paginate)
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('contract', function ($contractQuery) use ($search) {
                        $contractQuery->where('number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('contract.tenant.user', function ($userQuery) use ($search) {
                        $userQuery->where('first', 'like', "%{$search}%")
                            ->orWhere('last', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['contract_id'])) {
            $query->forContract($filters['contract_id']);
        }

        // Filter by tenant ID
        if (isset($filters['tenant_id']) && $filters['tenant_id']) {
            $query->whereHas('contract', function ($q) use ($filters) {
                $q->where('tenant_id', $filters['tenant_id']);
            });
        }

        // Filter by unit ID
        if (isset($filters['unit_id']) && $filters['unit_id']) {
            $query->whereHas('contract.units', function ($q) use ($filters) {
                $q->where('units.id', $filters['unit_id']);
            });
        }

        // Filter standalone invoices
        if (isset($filters['standalone']) && $filters['standalone'] === true) {
            $query->standalone();
        }

        // Filter contract-linked invoices
        if (isset($filters['linked_to_contracts']) && $filters['linked_to_contracts'] === true) {
            $query->linkedToContracts();
        }

        // Filter overdue invoices (only if true)
        if (isset($filters['overdue']) && $filters['overdue'] === true) {
            $query->overdue();
        }

        // Filter by date range
        if (isset($filters['start_date']) || isset($filters['end_date'])) {
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                // Both dates provided - use inPeriod scope
                $query->inPeriod($filters['start_date'], $filters['end_date']);
            } elseif (isset($filters['start_date'])) {
                // Only start date - filter from this date onwards
                $query->where(function ($q) use ($filters) {
                    $q->where('period_start', '>=', $filters['start_date'])
                        ->orWhere('period_end', '>=', $filters['start_date']);
                });
            } elseif (isset($filters['end_date'])) {
                // Only end date - filter up to this date
                $query->where(function ($q) use ($filters) {
                    $q->where('period_start', '<=', $filters['end_date'])
                        ->orWhere('period_end', '<=', $filters['end_date']);
                });
            }
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        // Filter by tenant IDs (for collectors - invoices.viewOwn)
        if (isset($filters['tenant_ids']) && is_array($filters['tenant_ids']) && !empty($filters['tenant_ids'])) {
            $query->whereHas('contract', function ($q) use ($filters) {
                $q->whereIn('tenant_id', $filters['tenant_ids']);
            });
        }

        // Apply sorting
        if (isset($filters['sort'])) {
            $sortField = $filters['sort'];
            $sortOrder = isset($filters['order']) && strtolower($filters['order']) === 'desc' ? 'desc' : 'asc';
            
            // Map frontend field names to database columns
            $fieldMap = [
                'number' => 'number',
                'contract' => 'contract_id',
                'total' => 'total',
                'due' => 'due',
                'dueDate' => 'due',
                'status' => 'status',
                'period_start' => 'period_start',
                'period_end' => 'period_end',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ];
            
            $dbField = $fieldMap[$sortField] ?? $sortField;
            $query->orderBy($dbField, $sortOrder);
        } else {
            // Default sorting
            $query->latest();
        }

        return $query->with(['contract.tenant.user', 'ownership', 'generatedBy', 'items', 'payments'])
            ->get();
    }

    /**
     * Find invoice by ID.
     */
    public function find(int $id): ?Invoice
    {
        return Invoice::with(['contract.tenant.user', 'ownership', 'generatedBy', 'items', 'payments'])
            ->find($id);
    }

    /**
     * Find invoice by UUID.
     */
    public function findByUuid(string $uuid): ?Invoice
    {
        return Invoice::where('uuid', $uuid)
            ->with(['contract.tenant.user', 'ownership', 'generatedBy', 'items', 'payments'])
            ->first();
    }

    /**
     * Find invoice by number.
     */
    public function findByNumber(string $number): ?Invoice
    {
        return Invoice::where('number', $number)
            ->with(['contract.tenant.user', 'ownership', 'generatedBy', 'items', 'payments'])
            ->first();
    }

    /**
     * Create a new invoice.
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    /**
     * Update invoice.
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh(['contract.tenant.user', 'ownership', 'generatedBy', 'items']);
    }

    /**
     * Delete invoice.
     */
    public function delete(Invoice $invoice): bool
    {
        return $invoice->delete();
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        return $invoice->fresh(['contract.tenant.user', 'ownership', 'generatedBy', 'items']);
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice): Invoice
    {
        $invoice->update([
            'status' => 'sent',
        ]);
        return $invoice->fresh(['contract.tenant.user', 'ownership', 'generatedBy', 'items']);
    }
}

