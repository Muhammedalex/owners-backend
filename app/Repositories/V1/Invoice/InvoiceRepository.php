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

        if (isset($filters['overdue'])) {
            $query->overdue();
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->inPeriod($filters['start_date'], $filters['end_date']);
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all invoices.
     */
    public function all(array $filters = []): Collection
    {
        $query = Invoice::query();

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

        if (isset($filters['overdue'])) {
            $query->overdue();
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->inPeriod($filters['start_date'], $filters['end_date']);
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])
            ->latest()
            ->get();
    }

    /**
     * Find invoice by ID.
     */
    public function find(int $id): ?Invoice
    {
        return Invoice::with(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])
            ->find($id);
    }

    /**
     * Find invoice by UUID.
     */
    public function findByUuid(string $uuid): ?Invoice
    {
        return Invoice::where('uuid', $uuid)
            ->with(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])
            ->first();
    }

    /**
     * Find invoice by number.
     */
    public function findByNumber(string $number): ?Invoice
    {
        return Invoice::where('number', $number)
            ->with(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])
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

