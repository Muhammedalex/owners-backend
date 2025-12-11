<?php

namespace App\Repositories\V1\Invoice\Interfaces;

use App\Models\V1\Invoice\InvoiceItem;
use Illuminate\Database\Eloquent\Collection;

interface InvoiceItemRepositoryInterface
{
    /**
     * Get all items for an invoice.
     */
    public function getByInvoice(int $invoiceId): Collection;

    /**
     * Find item by ID.
     */
    public function find(int $id): ?InvoiceItem;

    /**
     * Create a new item.
     */
    public function create(array $data): InvoiceItem;

    /**
     * Update item.
     */
    public function update(InvoiceItem $item, array $data): InvoiceItem;

    /**
     * Delete item.
     */
    public function delete(InvoiceItem $item): bool;

    /**
     * Delete all items for an invoice.
     */
    public function deleteByInvoice(int $invoiceId): bool;
}

