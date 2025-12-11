<?php

namespace App\Repositories\V1\Invoice;

use App\Models\V1\Invoice\InvoiceItem;
use App\Repositories\V1\Invoice\Interfaces\InvoiceItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InvoiceItemRepository implements InvoiceItemRepositoryInterface
{
    /**
     * Get all items for an invoice.
     */
    public function getByInvoice(int $invoiceId): Collection
    {
        return InvoiceItem::where('invoice_id', $invoiceId)
            ->get();
    }

    /**
     * Find item by ID.
     */
    public function find(int $id): ?InvoiceItem
    {
        return InvoiceItem::with('invoice')->find($id);
    }

    /**
     * Create a new item.
     */
    public function create(array $data): InvoiceItem
    {
        return InvoiceItem::create($data);
    }

    /**
     * Update item.
     */
    public function update(InvoiceItem $item, array $data): InvoiceItem
    {
        $item->update($data);
        return $item->fresh('invoice');
    }

    /**
     * Delete item.
     */
    public function delete(InvoiceItem $item): bool
    {
        return $item->delete();
    }

    /**
     * Delete all items for an invoice.
     */
    public function deleteByInvoice(int $invoiceId): bool
    {
        return InvoiceItem::where('invoice_id', $invoiceId)->delete();
    }
}

