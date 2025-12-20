<?php

namespace App\Services\V1\Invoice;

use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Invoice\InvoiceItem;
use App\Models\V1\Contract\Contract;
use App\Repositories\V1\Invoice\Interfaces\InvoiceRepositoryInterface;
use App\Services\V1\Document\DocumentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private DocumentService $documentService
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
            // Load relationships
            $invoice->load(['documents']);

            // Delete all documents
            foreach ($invoice->documents as $document) {
                $this->documentService->delete($document);
            }

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

    /**
     * Generate invoice from contract (supports multiple units).
     *
     * @param Contract $contract
     * @param array{start:string,end:string,due:string,amount?:float,tax_rate?:float} $period
     */
    public function generateFromContract(Contract $contract, array $period): Invoice
    {
        return DB::transaction(function () use ($contract, $period) {
            // Use base_rent instead of legacy rent field
            $amount = $period['amount'] ?? (float) ($contract->base_rent ?? 0);
            $taxRate = $period['tax_rate'] ?? 15.00;

            $invoice = $this->create([
                'contract_id' => $contract->id,
                'ownership_id' => $contract->ownership_id,
                'number' => $period['number'] ?? null,
                'period_start' => $period['start'],
                'period_end' => $period['end'],
                'due' => $period['due'],
                'amount' => $amount,
                'tax_rate' => $taxRate,
                'status' => $period['status'] ?? 'sent',
                'generated_by' => $period['generated_by'] ?? null,
                'generated_at' => $period['generated_at'] ?? now(),
            ]);

            // Create invoice items
            $this->createInvoiceItemsForContract($invoice, $contract);

            return $invoice;
        });
    }

    /**
     * Create invoice items for a contract (single or multiple units).
     */
    protected function createInvoiceItemsForContract(Invoice $invoice, Contract $contract): void
    {
        $units = $contract->relationLoaded('units') ? $contract->units : $contract->units()->get();

        if ($units->isEmpty()) {
            // Fallback: single item based on contract base_rent
            $baseRent = (float) ($contract->base_rent ?? 0);
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'rent',
                'description' => 'Rent for contract #' . $contract->number,
                'quantity' => 1,
                'unit_price' => $baseRent,
                'total' => $baseRent,
            ]);
            return;
        }

        // If multiple units: one item per unit (using pivot rent_amount when available)
        foreach ($units as $unit) {
            $pivotRent = $unit->pivot?->rent_amount;
            // Fallback to base_rent divided by units count if pivot rent not available
            $fallbackRent = $pivotRent !== null 
                ? (float) $pivotRent 
                : (float) (($contract->base_rent ?? 0) / max($units->count(), 1));
            $unitRent = $pivotRent !== null ? (float) $pivotRent : $fallbackRent;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'rent',
                'description' => 'Rent for unit ' . ($unit->number ?? $unit->id) . ' for period ' . $invoice->period_start . ' to ' . $invoice->period_end,
                'quantity' => 1,
                'unit_price' => $unitRent,
                'total' => $unitRent,
            ]);
        }
    }
}

