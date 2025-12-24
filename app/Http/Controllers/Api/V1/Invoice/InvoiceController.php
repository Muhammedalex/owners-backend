<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use App\Enums\V1\Invoice\InvoiceStatus;
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

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['search', 'status', 'contract_id', 'tenant_id', 'unit_id', 'start_date', 'end_date'])
        );
        
        // Add boolean filters if provided
        if ($request->has('standalone')) {
            $filters['standalone'] = $request->boolean('standalone');
        }
        if ($request->has('linked_to_contracts')) {
            $filters['linked_to_contracts'] = $request->boolean('linked_to_contracts');
        }
        if ($request->has('overdue')) {
            $overdueValue = $request->input('overdue');
            // Handle both string ('true'/'false') and boolean values
            if (is_string($overdueValue)) {
                $filters['overdue'] = filter_var($overdueValue, FILTER_VALIDATE_BOOLEAN);
            } else {
                $filters['overdue'] = (bool) $overdueValue;
            }
        }
        
        // Add sorting if provided
        if ($request->has('sort')) {
            $filters['sort'] = $request->input('sort');
            $filters['order'] = $request->input('order', 'asc'); // default to 'asc'
        }

        // If user is collector, apply collector scope
        // This will filter by assigned tenants, or show all if no tenants assigned
        if ($user->isCollector()) {
            $filters['collector_id'] = $user->id;
            $filters['collector_ownership_id'] = $ownershipId;
        }

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
     * 
     * If the user is the tenant associated with this invoice, mark it as VIEWED.
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

        // Load contract and tenant relationship to check if user is the tenant
        $invoice->load([
            'contract.tenant.user',
        ]);

        // Check if current user is the tenant for this invoice
        // Only mark as VIEWED if:
        // 1. Invoice is linked to a contract
        // 2. Contract has a tenant
        // 3. Tenant has a user
        // 4. Current user is that tenant user
        // 5. Invoice status is SENT (can only transition from SENT to VIEWED)
        $user = $request->user();
        if ($invoice->contract_id && 
            $invoice->contract && 
            $invoice->contract->tenant && 
            $invoice->contract->tenant->user_id &&
            $invoice->contract->tenant->user_id === $user->id &&
            $invoice->status === \App\Enums\V1\Invoice\InvoiceStatus::SENT) {
            // Mark as viewed - this will transition status from SENT to VIEWED
            $invoice->markAsViewed();
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

        $invoice = $this->invoiceService->update($invoice, $data, $request->user());

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
     * Update invoice status.
     * Ownership scope is mandatory from middleware.
     */
    public function updateStatus(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return $this->notFoundResponse('invoices.not_found');
        }

        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', InvoiceStatus::all())],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = InvoiceStatus::fromString($request->input('status'));
        if (!$newStatus) {
            return $this->errorResponse('messages.errors.invalid_status', 400);
        }

        try {
            $invoice = $this->invoiceService->updateStatus(
                $invoice,
                $newStatus,
                $request->input('reason'),
                $request->user()->id
            );

            return $this->successResponse(
                new InvoiceResource($invoice->load(['contract.tenant.user', 'ownership', 'generatedBy', 'items'])),
                'invoices.status_updated'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
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

    /**
     * Download invoice as PDF.
     * Ownership scope is mandatory from middleware.
     */
    public function downloadPdf(Request $request, Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('view', $invoice);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            abort(404, 'Invoice not found');
        }

        $pdfService = app(\App\Services\V1\Invoice\InvoicePdfService::class);
        
        return $pdfService->downloadPdf($invoice);
    }
}

