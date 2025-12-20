<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Invoice\StoreInvoiceRequest;
use App\Http\Requests\V1\Invoice\UpdateInvoiceRequest;
use App\Http\Resources\V1\Invoice\InvoiceResource;
use App\Models\V1\Invoice\Invoice;
use App\Services\V1\Invoice\InvoiceService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use HasLocalizedResponse;
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of the resource.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['search', 'status', 'contract_id', 'overdue', 'start_date', 'end_date'])
        );

        if ($perPage === -1) {
            $invoices = $this->invoiceService->all($filters);

            return $this->successResponse(
                InvoiceResource::collection($invoices)
            );
        }

        $invoices = $this->invoiceService->paginate($perPage, $filters);

        return $this->successResponse(
            InvoiceResource::collection($invoices->items()),
            null,
            200,
            [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $data = $request->validated();
        $data['ownership_id'] = $ownershipId; // MANDATORY
        $data['generated_by'] = $request->user()->id;

        $invoice = $this->invoiceService->create($data);

        return $this->successResponse(
            new InvoiceResource($invoice->load(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])),
            'invoices.created',
            201
        );
    }

    /**
     * Display the specified resource.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return $this->notFoundResponse('invoices.not_found');
        }

        $invoice = $this->invoiceService->findByUuid($invoice->uuid);

        if (!$invoice) {
            return $this->notFoundResponse('invoices.not_found');
        }

        // Load all related data
        $invoice->load([
            'contract.units',
            'contract.tenant.user',
            'contract.ownership',
            'ownership',
            'generatedBy',
            'items',
            'payments.confirmedBy',
        ]);

        return $this->successResponse(new InvoiceResource($invoice));
    }

    /**
     * Update the specified resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return $this->notFoundResponse('invoices.not_found');
        }

        $data = $request->validated();
        // Ownership ID cannot be changed via update
        unset($data['ownership_id']);

        $invoice = $this->invoiceService->update($invoice, $data);

        return $this->successResponse(
            new InvoiceResource($invoice->load(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])),
            'invoices.updated'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);

        $this->invoiceService->delete($invoice);

        return $this->successResponse(null, 'invoices.deleted');
    }

    /**
     * Mark invoice as paid.
     * Ownership scope is mandatory from middleware.
     */
    public function markAsPaid(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return $this->notFoundResponse('invoices.not_found');
        }

        $invoice = $this->invoiceService->markAsPaid($invoice);

        return $this->successResponse(
            new InvoiceResource($invoice->load(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])),
            'invoices.marked_paid'
        );
    }

    /**
     * Mark invoice as sent.
     * Ownership scope is mandatory from middleware.
     */
    public function markAsSent(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return $this->notFoundResponse('invoices.not_found');
        }

        $invoice = $this->invoiceService->markAsSent($invoice);

        return $this->successResponse(
            new InvoiceResource($invoice->load(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])),
            'invoices.marked_sent'
        );
    }
}

