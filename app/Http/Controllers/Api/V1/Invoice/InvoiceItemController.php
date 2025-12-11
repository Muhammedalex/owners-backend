<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Invoice\StoreInvoiceItemRequest;
use App\Http\Requests\V1\Invoice\UpdateInvoiceItemRequest;
use App\Http\Resources\V1\Invoice\InvoiceItemResource;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Invoice\InvoiceItem;
use App\Services\V1\Invoice\InvoiceItemService;
use App\Services\V1\Invoice\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceItemController extends Controller
{
    public function __construct(
        private InvoiceItemService $invoiceItemService,
        private InvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of invoice items.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found or access denied.',
            ], 404);
        }

        $items = $this->invoiceItemService->getByInvoice($invoice->id);

        return response()->json([
            'success' => true,
            'data' => InvoiceItemResource::collection($items),
        ]);
    }

    /**
     * Store a newly created invoice item.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StoreInvoiceItemRequest $request, Invoice $invoice): JsonResponse
    {
        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found or access denied.',
            ], 404);
        }

        // Only allow adding items to draft invoices
        if (!$invoice->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'Items can only be added to draft invoices.',
            ], 422);
        }

        $data = $request->validated();
        $data['invoice_id'] = $invoice->id;

        $item = $this->invoiceItemService->create($data);

        // Recalculate invoice totals
        $invoice = $this->invoiceService->find($invoice->id);
        $itemsTotal = $invoice->calculateTotalFromItems();
        $tax = $itemsTotal * ($invoice->tax_rate / 100);
        $total = $itemsTotal + $tax;

        $this->invoiceService->update($invoice, [
            'amount' => $itemsTotal,
            'tax' => $tax,
            'total' => $total,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice item created successfully.',
            'data' => new InvoiceItemResource($item->load('invoice')),
        ], 201);
    }

    /**
     * Display the specified invoice item.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, Invoice $invoice, InvoiceItem $item): JsonResponse
    {
        $this->authorize('view', $invoice);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found or access denied.',
            ], 404);
        }

        // Verify item belongs to invoice
        if ($item->invoice_id !== $invoice->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item not found for this invoice.',
            ], 404);
        }

        $item = $this->invoiceItemService->find($item->id);

        return response()->json([
            'success' => true,
            'data' => new InvoiceItemResource($item->load('invoice')),
        ]);
    }

    /**
     * Update the specified invoice item.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdateInvoiceItemRequest $request, Invoice $invoice, InvoiceItem $item): JsonResponse
    {
        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found or access denied.',
            ], 404);
        }

        // Only allow updating items in draft invoices
        if (!$invoice->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'Items can only be updated in draft invoices.',
            ], 422);
        }

        // Verify item belongs to invoice
        if ($item->invoice_id !== $invoice->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item not found for this invoice.',
            ], 404);
        }

        $item = $this->invoiceItemService->update($item, $request->validated());

        // Recalculate invoice totals
        $invoice = $this->invoiceService->find($invoice->id);
        $itemsTotal = $invoice->calculateTotalFromItems();
        $tax = $itemsTotal * ($invoice->tax_rate / 100);
        $total = $itemsTotal + $tax;

        $this->invoiceService->update($invoice, [
            'amount' => $itemsTotal,
            'tax' => $tax,
            'total' => $total,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice item updated successfully.',
            'data' => new InvoiceItemResource($item->load('invoice')),
        ]);
    }

    /**
     * Remove the specified invoice item.
     * Ownership scope is mandatory from middleware.
     */
    public function destroy(Request $request, Invoice $invoice, InvoiceItem $item): JsonResponse
    {
        $this->authorize('update', $invoice);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found or access denied.',
            ], 404);
        }

        // Only allow deleting items from draft invoices
        if (!$invoice->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'Items can only be deleted from draft invoices.',
            ], 422);
        }

        // Verify item belongs to invoice
        if ($item->invoice_id !== $invoice->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item not found for this invoice.',
            ], 404);
        }

        $this->invoiceItemService->delete($item);

        // Recalculate invoice totals
        $invoice = $this->invoiceService->find($invoice->id);
        $itemsTotal = $invoice->calculateTotalFromItems();
        $tax = $itemsTotal * ($invoice->tax_rate / 100);
        $total = $itemsTotal + $tax;

        $this->invoiceService->update($invoice, [
            'amount' => $itemsTotal,
            'tax' => $tax,
            'total' => $total,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice item deleted successfully.',
        ]);
    }
}

