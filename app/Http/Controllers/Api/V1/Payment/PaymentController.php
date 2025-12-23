<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Payment\StorePaymentRequest;
use App\Http\Requests\V1\Payment\UpdatePaymentRequest;
use App\Http\Resources\V1\Payment\PaymentResource;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Payment\Payment;
use App\Services\V1\Payment\PaymentService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use HasLocalizedResponse;
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Display a listing of the resource.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['search', 'status', 'invoice_id', 'method'])
        );

        // If user is collector, apply collector scope
        // This will filter by assigned tenants, or show all if no tenants assigned
        if ($user->isCollector()) {
            $filters['collector_id'] = $user->id;
            $filters['collector_ownership_id'] = $ownershipId;
        }

        if ($perPage === -1) {
            $payments = $this->paymentService->all($filters);

            return $this->successResponse(
                PaymentResource::collection($payments)
            );
        }

        $payments = $this->paymentService->paginate($perPage, $filters);

        return $this->successResponse(
            PaymentResource::collection($payments->items()),
            null,
            200,
            [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $data = $request->validated();
        $data['ownership_id'] = $ownershipId; // MANDATORY

        $payment = $this->paymentService->create($data);

        return $this->successResponse(
            new PaymentResource($payment->load(['invoice.contract.tenant.user', 'ownership', 'confirmedBy'])),
            'payments.created',
            201
        );
    }

    /**
     * Display the specified resource.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $payment->ownership_id != $ownershipId) {
            return $this->notFoundResponse('payments.not_found');
        }

        $payment = $this->paymentService->findByUuid($payment->uuid);

        if (!$payment) {
            return $this->notFoundResponse('payments.not_found');
        }

        // Load all related data
        $payment->load([
            'invoice.contract.units',
            'invoice.contract.tenant.user',
            'invoice.contract.ownership',
            'invoice.items',
            'invoice.payments',
            'invoice.ownership',
            'invoice.generatedBy',
            'ownership',
            'confirmedBy',
        ]);

        return $this->successResponse(new PaymentResource($payment));
    }

    /**
     * Update the specified resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $payment->ownership_id != $ownershipId) {
            return $this->notFoundResponse('payments.not_found');
        }

        $data = $request->validated();
        // Ownership ID cannot be changed via update
        unset($data['ownership_id']);

        $payment = $this->paymentService->update($payment, $data);

        return $this->successResponse(
            new PaymentResource($payment->load(['invoice.contract.tenant.user', 'ownership', 'confirmedBy'])),
            'payments.updated'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);

        $this->paymentService->delete($payment);

        return $this->successResponse(null, 'payments.deleted');
    }

    /**
     * Mark payment as paid (manual confirmation).
     * Ownership scope is mandatory from middleware.
     */
    public function markAsPaid(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $payment->ownership_id != $ownershipId) {
            return $this->notFoundResponse('payments.not_found');
        }

        $payment = $this->paymentService->markAsPaid($payment, $request->user()->id);

        return $this->successResponse(
            new PaymentResource($payment->load(['invoice.contract.tenant.user', 'ownership', 'confirmedBy'])),
            'payments.marked_paid'
        );
    }

    /**
     * Mark payment as unpaid.
     * Ownership scope is mandatory from middleware.
     */
    public function markAsUnpaid(Request $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $payment->ownership_id != $ownershipId) {
            return $this->notFoundResponse('payments.not_found');
        }

        $payment = $this->paymentService->markAsUnpaid($payment);

        return $this->successResponse(
            new PaymentResource($payment->load(['invoice.contract.tenant.user', 'ownership', 'confirmedBy'])),
            'payments.marked_unpaid'
        );
    }

    /**
     * Get payments for an invoice.
     * Ownership scope is mandatory from middleware.
     */
    public function getByInvoice(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invoice->ownership_id != $ownershipId) {
            return $this->notFoundResponse('invoices.not_found');
        }

        $payments = $this->paymentService->getByInvoice($invoice->id);

        return $this->successResponse(PaymentResource::collection($payments));
    }
}

