<?php

namespace App\Http\Controllers\Api\V1\Contract;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Contract\StoreContractTermRequest;
use App\Http\Requests\V1\Contract\UpdateContractTermRequest;
use App\Http\Resources\V1\Contract\ContractTermResource;
use App\Models\V1\Contract\Contract;
use App\Models\V1\Contract\ContractTerm;
use App\Services\V1\Contract\ContractTermService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractTermController extends Controller
{
    public function __construct(
        private ContractTermService $contractTermService
    ) {}

    /**
     * Display a listing of contract terms.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request, Contract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found or access denied.',
            ], 404);
        }

        $terms = $this->contractTermService->getByContract($contract->id);

        return response()->json([
            'success' => true,
            'data' => ContractTermResource::collection($terms),
        ]);
    }

    /**
     * Store a newly created contract term.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StoreContractTermRequest $request, Contract $contract): JsonResponse
    {
        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found or access denied.',
            ], 404);
        }

        $data = $request->validated();
        $data['contract_id'] = $contract->id;

        $term = $this->contractTermService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Contract term created successfully.',
            'data' => new ContractTermResource($term->load('contract')),
        ], 201);
    }

    /**
     * Display the specified contract term.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, Contract $contract, ContractTerm $term): JsonResponse
    {
        $this->authorize('view', $contract);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found or access denied.',
            ], 404);
        }

        // Verify term belongs to contract
        if ($term->contract_id !== $contract->id) {
            return response()->json([
                'success' => false,
                'message' => 'Contract term not found for this contract.',
            ], 404);
        }

        $term = $this->contractTermService->find($term->id);

        return response()->json([
            'success' => true,
            'data' => new ContractTermResource($term->load('contract')),
        ]);
    }

    /**
     * Update the specified contract term.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdateContractTermRequest $request, Contract $contract, ContractTerm $term): JsonResponse
    {
        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found or access denied.',
            ], 404);
        }

        // Verify term belongs to contract
        if ($term->contract_id !== $contract->id) {
            return response()->json([
                'success' => false,
                'message' => 'Contract term not found for this contract.',
            ], 404);
        }

        $term = $this->contractTermService->update($term, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Contract term updated successfully.',
            'data' => new ContractTermResource($term->load('contract')),
        ]);
    }

    /**
     * Remove the specified contract term.
     * Ownership scope is mandatory from middleware.
     */
    public function destroy(Request $request, Contract $contract, ContractTerm $term): JsonResponse
    {
        $this->authorize('update', $contract);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $contract->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Contract not found or access denied.',
            ], 404);
        }

        // Verify term belongs to contract
        if ($term->contract_id !== $contract->id) {
            return response()->json([
                'success' => false,
                'message' => 'Contract term not found for this contract.',
            ], 404);
        }

        $this->contractTermService->delete($term);

        return response()->json([
            'success' => true,
            'message' => 'Contract term deleted successfully.',
        ]);
    }
}

