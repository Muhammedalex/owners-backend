<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Tenant\StoreTenantRequest;
use App\Http\Requests\V1\Tenant\UpdateTenantRequest;
use App\Http\Resources\V1\Tenant\TenantResource;
use App\Models\V1\Tenant\Tenant;
use App\Services\V1\Tenant\TenantService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    use HasLocalizedResponse;
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Display a listing of the resource.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $perPage = $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['search', 'rating', 'employment'])
        );

        $tenants = $this->tenantService->paginate($perPage, $filters);

        return $this->successResponse(
            TenantResource::collection($tenants->items()),
            null,
            200,
            [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $data = $request->validated();
        $data['ownership_id'] = $ownershipId; // MANDATORY

        $tenant = $this->tenantService->create($data);

        return $this->successResponse(
            new TenantResource($tenant->load(['user', 'ownership'])),
            'tenants.created',
            201
        );
    }

    /**
     * Display the specified resource.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $tenant->ownership_id != $ownershipId) {
            return $this->notFoundResponse('tenants.access_denied');
        }

        $tenant = $this->tenantService->find($tenant->id);

        if (!$tenant) {
            return $this->notFoundResponse('tenants.not_found');
        }

        // Load all related data
        $tenant->load([
            'user',
            'ownership',
            'contracts.unit',
            'contracts.tenant',
            'contracts.ownership',
        ]);

        return $this->successResponse(new TenantResource($tenant));
    }

    /**
     * Update the specified resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $tenant->ownership_id != $ownershipId) {
            return $this->notFoundResponse('tenants.access_denied');
        }

        $data = $request->validated();
        // Ownership ID cannot be changed via update
        unset($data['ownership_id']);

        $tenant = $this->tenantService->update($tenant, $data);

        return $this->successResponse(
            new TenantResource($tenant->load(['user', 'ownership'])),
            'tenants.updated'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->authorize('delete', $tenant);

        $this->tenantService->delete($tenant);

        return $this->successResponse(null, 'tenants.deleted');
    }
}

