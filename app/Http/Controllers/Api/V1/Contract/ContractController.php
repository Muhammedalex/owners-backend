<?php

namespace App\Http\Controllers\Api\V1\Contract;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Contract\StoreContractRequest;
use App\Http\Requests\V1\Contract\UpdateContractRequest;
use App\Http\Resources\V1\Contract\ContractResource;
use App\Models\V1\Contract\Contract;
use App\Services\V1\Contract\ContractService;
use App\Services\V1\Document\DocumentService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    use HasLocalizedResponse;
    public function __construct(
        private ContractService $contractService,
        private DocumentService $documentService
    ) {}

    /**
     * Display a listing of the resource.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Contract::class);
 
        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['search', 'status', 'tenant_id', 'unit_id', 'start_date', 'end_date', 'ejar_code'])
        );

        // If user is collector, apply collector scope
        // This will filter by assigned tenants, or show all if no tenants assigned
        if ($user->isCollector()) {
            $filters['collector_id'] = $user->id;
            $filters['collector_ownership_id'] = $ownershipId;
        }

        if ($perPage === -1) {
            $contracts = $this->contractService->all($filters);

            return $this->successResponse(
                ContractResource::collection($contracts)
            );
        }

        $contracts = $this->contractService->paginate($perPage, $filters);

        return $this->successResponse(
            ContractResource::collection($contracts->items()),
            null,
            200,
            [
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StoreContractRequest $request): JsonResponse
    {
        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $data = $request->validated();
        $data['ownership_id'] = $ownershipId; // MANDATORY
        $data['created_by'] = $request->user()->id;

        $contract = $this->contractService->create($data);

        // Handle Ejar PDF upload if provided
        if ($request->hasFile('ejar_pdf')) {
            $file = $request->file('ejar_pdf');

            // Delete old ejar_pdf if exists (safety in case of re-create scenarios)
            $oldDoc = $contract->getDocument('ejar_pdf');
            if ($oldDoc) {
                $this->documentService->delete($oldDoc);
            }

            $this->documentService->upload(
                entity: $contract,
                file: $file,
                type: 'ejar_pdf',
                ownershipId: $ownershipId,
                title: 'Ejar Contract PDF',
                uploadedBy: $request->user()->id,
                description: 'Ejar platform contract PDF'
            );
        }

        return $this->successResponse(
            new ContractResource($contract->load(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents'])),
            'contracts.created',
            201
        );
    }

    /**
     * Display the specified resource.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, Contract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return $this->notFoundResponse('contracts.not_found');
        }

        $contract = $this->contractService->findByUuid($contract->uuid);

        if (!$contract) {
            return $this->notFoundResponse('contracts.not_found');
        }

        // Load all related data
        $contract->load([
            'units',
            'tenant.user',
            'ownership',
            'createdBy',
            'approvedBy',
            'parent',
            'children',
            'terms',
            'documents',
            'invoices.items',
            'invoices.payments',
        ]);

        return $this->successResponse(new ContractResource($contract));
    }

    /**
     * Update the specified resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdateContractRequest $request, Contract $contract): JsonResponse
    {
        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return $this->notFoundResponse('contracts.not_found');
        }

        $data = $request->validated();
        // Ownership ID cannot be changed via update
        unset($data['ownership_id']);

        $contract = $this->contractService->update($contract, $data);

        // Handle Ejar PDF upload if provided
        if ($request->hasFile('ejar_pdf')) {
            $file = $request->file('ejar_pdf');

            // Delete old ejar_pdf if exists
            $oldDoc = $contract->getDocument('ejar_pdf');
            if ($oldDoc) {
                $this->documentService->delete($oldDoc);
            }

            $this->documentService->upload(
                entity: $contract,
                file: $file,
                type: 'ejar_pdf',
                ownershipId: $contract->ownership_id,
                title: 'Ejar Contract PDF',
                uploadedBy: $request->user()->id,
                description: 'Ejar platform contract PDF'
            );
        }

        return $this->successResponse(
            new ContractResource($contract->load(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents'])),
            'contracts.updated'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contract $contract): JsonResponse
    {
        $this->authorize('delete', $contract);

        $this->contractService->delete($contract);

        return $this->successResponse(null, 'contracts.deleted');
    }

    /**
     * Approve contract.
     * Ownership scope is mandatory from middleware.
     */
    public function approve(Request $request, Contract $contract): JsonResponse
    {
        $this->authorize('approve', $contract);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return $this->notFoundResponse('contracts.not_found');
        }

        $contract = $this->contractService->approve($contract, $request->user()->id);

        return $this->successResponse(
            new ContractResource($contract->load(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents'])),
            'contracts.approved'
        );
    }

    /**
     * Cancel contract.
     * Only works on pending or draft contracts.
     * Ownership scope is mandatory from middleware.
     */
    public function cancel(Request $request, Contract $contract): JsonResponse
    {
        $this->authorize('cancel', $contract);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return $this->notFoundResponse('contracts.not_found');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $contract = $this->contractService->cancel($contract, $validated['reason'] ?? null);

        return $this->successResponse(
            new ContractResource($contract->load(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents'])),
            'contracts.cancelled'
        );
    }

    /**
     * Terminate contract.
     * Only works on active contracts.
     * Ownership scope is mandatory from middleware.
     */
    public function terminate(Request $request, Contract $contract): JsonResponse
    {
        $this->authorize('terminate', $contract);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return $this->notFoundResponse('contracts.not_found');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $contract = $this->contractService->terminate($contract, $validated['reason'] ?? null);

        return $this->successResponse(
            new ContractResource($contract->load(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents'])),
            'contracts.terminated'
        );
    }
}

