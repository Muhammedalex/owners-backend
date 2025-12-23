<?php

namespace App\Repositories\V1\Payment;

use App\Models\V1\Payment\Payment;
use App\Repositories\V1\Payment\Interfaces\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * Get all payments with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Payment::query();

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
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($invoiceQuery) use ($search) {
                        $invoiceQuery->where('number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('invoice.contract.tenant.user', function ($userQuery) use ($search) {
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

        if (isset($filters['invoice_id'])) {
            $query->forInvoice($filters['invoice_id']);
        }

        if (isset($filters['method'])) {
            $query->withMethod($filters['method']);
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['invoice.contract.tenant.user', 'ownership', 'confirmedBy'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all payments.
     */
    public function all(array $filters = []): Collection
    {
        $query = Payment::query();

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
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('invoice', function ($invoiceQuery) use ($search) {
                        $invoiceQuery->where('number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('invoice.contract.tenant.user', function ($userQuery) use ($search) {
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

        if (isset($filters['invoice_id'])) {
            $query->forInvoice($filters['invoice_id']);
        }

        if (isset($filters['method'])) {
            $query->withMethod($filters['method']);
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['invoice.contract.tenant.user', 'ownership', 'confirmedBy'])
            ->latest()
            ->get();
    }

    /**
     * Find payment by ID.
     */
    public function find(int $id): ?Payment
    {
        return Payment::with(['invoice.contract.tenant.user', 'ownership', 'confirmedBy'])
            ->find($id);
    }

    /**
     * Find payment by UUID.
     */
    public function findByUuid(string $uuid): ?Payment
    {
        return Payment::where('uuid', $uuid)
            ->with(['invoice.contract.tenant.user', 'ownership', 'confirmedBy'])
            ->first();
    }

    /**
     * Get payments for an invoice.
     */
    public function getByInvoice(int $invoiceId): Collection
    {
        return Payment::where('invoice_id', $invoiceId)
            ->with(['invoice.contract.tenant.user', 'ownership', 'confirmedBy'])
            ->latest()
            ->get();
    }

    /**
     * Create a new payment.
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    /**
     * Update payment.
     */
    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);
        return $payment->fresh(['invoice.contract.tenant.user', 'ownership', 'confirmedBy']);
    }

    /**
     * Delete payment.
     */
    public function delete(Payment $payment): bool
    {
        return $payment->delete();
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(Payment $payment, int $confirmedBy): Payment
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'confirmed_by' => $confirmedBy,
        ]);
        return $payment->fresh(['invoice.contract.tenant.user', 'ownership', 'confirmedBy']);
    }

    /**
     * Mark payment as unpaid.
     */
    public function markAsUnpaid(Payment $payment): Payment
    {
        $payment->update([
            'status' => 'unpaid',
            'paid_at' => null,
            'confirmed_by' => null,
        ]);
        return $payment->fresh(['invoice.contract.tenant.user', 'ownership', 'confirmedBy']);
    }
}

