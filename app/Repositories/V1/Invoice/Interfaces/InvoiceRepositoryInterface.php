<?php

namespace App\Repositories\V1\Invoice\Interfaces;

use App\Models\V1\Invoice\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InvoiceRepositoryInterface
{
    /**
     * Get all invoices with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all invoices.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find invoice by ID.
     */
    public function find(int $id): ?Invoice;

    /**
     * Find invoice by UUID.
     */
    public function findByUuid(string $uuid): ?Invoice;

    /**
     * Find invoice by number.
     */
    public function findByNumber(string $number): ?Invoice;

    /**
     * Create a new invoice.
     */
    public function create(array $data): Invoice;

    /**
     * Update invoice.
     */
    public function update(Invoice $invoice, array $data): Invoice;

    /**
     * Delete invoice.
     */
    public function delete(Invoice $invoice): bool;

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice): Invoice;

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(Invoice $invoice): Invoice;
}

