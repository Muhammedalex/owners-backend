<?php

namespace App\Services\V1\Invoice;

use App\Models\V1\Invoice\Invoice;
use App\Repositories\V1\Invoice\Interfaces\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    /**
     * Get all invoices with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginate($perPage, $filters);
    }

    /**
     * Get all invoices.
     */
    public function all(array $filters = []): Collection
    {
        return $this->invoiceRepository->all($filters);
    }

    /**
     * Find invoice by ID.
     */
    public function find(int $id): ?Invoice
    {
        return $this->invoiceRepository->find($id);
    }

    /**
     * Find invoice by UUID.
     */
    public function findByUuid(string $uuid): ?Invoice
    {
        return $this->invoiceRepository->findByUuid($uuid);
    }

    /**
     * Find invoice by number.
     */
    public function findByNumber(string $number): ?Invoice
    {
        return $this->invoiceRepository->findByNumber($number);
    }

    /**
     * Create a new invoice.
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // Calculate tax if not provided
            if (!isset($data['tax']) && isset($data['amount']) && isset($data['tax_rate'])) {
                $data['tax'] = $data['amount'] * ($data['tax_rate'] / 100);
            }

            // Calculate total if not provided
            if (!isset($data['total']) && isset($data['amount']) && isset($data['tax'])) {
                $data['total'] = $data['amount'] + $data['tax'];
            }

            // Set generated_at if not provided
            if (!isset($data['generated_at'])) {
                $data['generated_at'] = now();
            }

            return $this->invoiceRepository->create($data);
        });
    }

    /**
     * Update invoice.
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            // Recalculate tax if amount or tax_rate changed
            if (isset($data['amount']) || isset($data['tax_rate'])) {
                $amount = $data['amount'] ?? $invoice->amount;
                $taxRate = $data['tax_rate'] ?? $invoice->tax_rate;
                $data['tax'] = $amount * ($taxRate / 100);
            }

            // Recalculate total if amount or tax changed
            if (isset($data['amount']) || isset($data['tax'])) {
                $amount = $data['amount'] ?? $invoice->amount;
                $tax = $data['tax'] ?? $invoice->tax;
                $data['total'] = $amount + $tax;
            }

            return $this->invoiceRepository->update($invoice, $data);
        });
    }

    /**
     * Delete invoice.
     */
    public function delete(Invoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            return $this->invoiceRepository->delete($invoice);
        });
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            return $this->invoiceRepository->markAsPaid($invoice);
        });
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            return $this->invoiceRepository->markAsSent($invoice);
        });
    }
}

