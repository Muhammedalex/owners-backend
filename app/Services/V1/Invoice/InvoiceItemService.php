<?php

namespace App\Services\V1\Invoice;

use App\Models\V1\Invoice\InvoiceItem;
use App\Repositories\V1\Invoice\Interfaces\InvoiceItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceItemService
{
    public function __construct(
        private InvoiceItemRepositoryInterface $invoiceItemRepository
    ) {}

    /**
     * Get all items for an invoice.
     */
    public function getByInvoice(int $invoiceId): Collection
    {
        return $this->invoiceItemRepository->getByInvoice($invoiceId);
    }

    /**
     * Find item by ID.
     */
    public function find(int $id): ?InvoiceItem
    {
        return $this->invoiceItemRepository->find($id);
    }

    /**
     * Create a new item.
     */
    public function create(array $data): InvoiceItem
    {
        return DB::transaction(function () use ($data) {
            // Auto-calculate total if not provided
            if (!isset($data['total']) && isset($data['quantity']) && isset($data['unit_price'])) {
                $data['total'] = $data['quantity'] * $data['unit_price'];
            }

            return $this->invoiceItemRepository->create($data);
        });
    }

    /**
     * Update item.
     */
    public function update(InvoiceItem $item, array $data): InvoiceItem
    {
        return DB::transaction(function () use ($item, $data) {
            // Recalculate total if quantity or unit_price changed
            if (isset($data['quantity']) || isset($data['unit_price'])) {
                $quantity = $data['quantity'] ?? $item->quantity;
                $unitPrice = $data['unit_price'] ?? $item->unit_price;
                $data['total'] = $quantity * $unitPrice;
            }

            return $this->invoiceItemRepository->update($item, $data);
        });
    }

    /**
     * Delete item.
     */
    public function delete(InvoiceItem $item): bool
    {
        return DB::transaction(function () use ($item) {
            return $this->invoiceItemRepository->delete($item);
        });
    }

    /**
     * Delete all items for an invoice.
     */
    public function deleteByInvoice(int $invoiceId): bool
    {
        return DB::transaction(function () use ($invoiceId) {
            return $this->invoiceItemRepository->deleteByInvoice($invoiceId);
        });
    }
}

