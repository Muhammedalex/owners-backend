<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Tenant\StoreTenantRequest;
use App\Http\Requests\V1\Tenant\UpdateTenantRequest;
use App\Http\Resources\V1\Tenant\TenantResource;
use App\Models\V1\Tenant\Tenant;
use App\Services\V1\Media\MediaService;
use App\Services\V1\Tenant\TenantService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    use HasLocalizedResponse;
    public function __construct(
        private TenantService $tenantService,
        private MediaService $mediaService
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

        $user = $request->user();
        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['search', 'rating', 'employment'])
        );

        // If user is collector, apply collector scope
        // This will filter by assigned tenants, or show all if no tenants assigned
        if ($user->isCollector()) {
            $filters['collector_id'] = $user->id;
            $filters['collector_ownership_id'] = $ownershipId;
        }

        if ($perPage === -1) {
            $tenants = $this->tenantService->all($filters);

            return $this->successResponse(
                TenantResource::collection($tenants)
            );
        }

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

        // Handle media uploads (ID, commercial registration, municipality license)
        $this->handleMediaUploads($request, $tenant, $ownershipId);

        return $this->successResponse(
            new TenantResource($tenant->load(['user', 'ownership', 'mediaFiles'])),
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
            'mediaFiles',
            'contracts.units',
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
        
        // Refresh tenant to ensure we have latest data
        $tenant->refresh();

        // Handle media uploads (ID, commercial registration, municipality license)
        // This will delete old images before uploading new ones
        $this->handleMediaUploads($request, $tenant, $ownershipId);
        
        // Refresh tenant to get latest media files
        $tenant->refresh();

        return $this->successResponse(
            new TenantResource($tenant->load(['user', 'ownership', 'mediaFiles'])),
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

    /**
     * Handle media uploads for tenant on store/update.
     * Deletes old images before uploading new ones.
     */
    private function handleMediaUploads(Request $request, Tenant $tenant, int $ownershipId): void
    {
        $userId = $request->user()?->id;

        // ID Document Image
        if ($request->hasFile('id_document_image')) {
            $file = $request->file('id_document_image');
            
            // Delete old ID document image if exists
            $oldImage = $tenant->mediaFiles()
                ->where('type', 'tenant_id_document')
                ->first();
            
            if ($oldImage) {
                $this->mediaService->delete($oldImage);
            }

            // Upload new image
            $this->mediaService->upload(
                entity: $tenant,
                file: $file,
                type: 'tenant_id_document',
                ownershipId: $ownershipId,
                uploadedBy: $userId,
            );
        }

        // Commercial Registration Image
        if ($request->hasFile('commercial_registration_image')) {
            $file = $request->file('commercial_registration_image');
            
            // Delete old commercial registration image if exists
            $oldImage = $tenant->mediaFiles()
                ->where('type', 'tenant_cr_document')
                ->first();
            
            if ($oldImage) {
                $this->mediaService->delete($oldImage);
            }

            // Upload new image
            $this->mediaService->upload(
                entity: $tenant,
                file: $file,
                type: 'tenant_cr_document',
                ownershipId: $ownershipId,
                uploadedBy: $userId,
            );
        }

        // Municipality License Image
        if ($request->hasFile('municipality_license_image')) {
            $file = $request->file('municipality_license_image');
            
            // Delete old municipality license image if exists
            $oldImage = $tenant->mediaFiles()
                ->where('type', 'tenant_municipality_license')
                ->first();
            
            if ($oldImage) {
                $this->mediaService->delete($oldImage);
            }

            // Upload new image
            $this->mediaService->upload(
                entity: $tenant,
                file: $file,
                type: 'tenant_municipality_license',
                ownershipId: $ownershipId,
                uploadedBy: $userId,
            );
        }
    }
}

