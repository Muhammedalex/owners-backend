<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\StoreOwnershipRequest;
use App\Http\Requests\V1\Ownership\UpdateOwnershipRequest;
use App\Http\Resources\V1\Ownership\OwnershipResource;
use App\Models\V1\Ownership\Ownership;
use App\Services\V1\Auth\AuthService;
use App\Services\V1\Ownership\OwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnershipController extends Controller
{
    public function __construct(
        private OwnershipService $ownershipService,
        private AuthService $authService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ownership::class);

        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['search', 'type', 'ownership_type', 'city', 'active']);

        // Apply ownership scope if user is not Super Admin
        $user = $request->user();
        if (!$user->isSuperAdmin()) {
            // Filter by user's ownerships
            $ownershipIds = $user->getOwnershipIds();
            if (empty($ownershipIds)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                    ],
                ]);
            }
            // Add ownership IDs filter
            $filters['ownership_ids'] = $ownershipIds;
        }

        if ($perPage === -1) {
            $ownerships = $this->ownershipService->all($filters);

            return response()->json([
                'success' => true,
                'data' => OwnershipResource::collection($ownerships),
            ]);
        }

        $ownerships = $this->ownershipService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => OwnershipResource::collection($ownerships->items()),
            'meta' => [
                'current_page' => $ownerships->currentPage(),
                'last_page' => $ownerships->lastPage(),
                'per_page' => $ownerships->perPage(),
                'total' => $ownerships->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOwnershipRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $ownership = $this->ownershipService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Ownership created successfully.',
            'data' => new OwnershipResource($ownership->load(['createdBy', 'boardMembers.user', 'userMappings.user'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ownership $ownership): JsonResponse
    {
        $this->authorize('view', $ownership);

        $ownership = $this->ownershipService->findByUuid($ownership->uuid);

        if (!$ownership) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership not found.',
            ], 404);
        }

        // Load all related data with nested relations
        $ownership->load([
            'createdBy',
            'boardMembers.user',
            'userMappings.user',
            'portfolios.locations',
            'portfolios.buildings.buildingFloors',
            'portfolios.buildings.ownership',
            'buildings.portfolio',
            'buildings.buildingFloors.units.specifications',
            'buildings.ownership',
            'units.building',
            'units.floor',
            'units.specifications',
            'units.ownership',
        ]);

        // Load counts
        $ownership->loadCount([
            'portfolios',
            'buildings',
            'units',
        ]);

        return response()->json([
            'success' => true,
            'data' => new OwnershipResource($ownership),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOwnershipRequest $request, Ownership $ownership): JsonResponse
    {
        $ownership = $this->ownershipService->update($ownership, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ownership updated successfully.',
            'data' => new OwnershipResource($ownership->load(['createdBy', 'boardMembers.user', 'userMappings.user'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ownership $ownership): JsonResponse
    {
        $this->authorize('delete', $ownership);

        $this->ownershipService->delete($ownership);

        return response()->json([
            'success' => true,
            'message' => 'Ownership deleted successfully.',
        ]);
    }

    /**
     * Activate ownership.
     */
    public function activate(Ownership $ownership): JsonResponse
    {
        $this->authorize('activate', $ownership);

        $ownership = $this->ownershipService->activate($ownership);

        return response()->json([
            'success' => true,
            'message' => 'Ownership activated successfully.',
            'data' => new OwnershipResource($ownership->load(['createdBy', 'boardMembers.user', 'userMappings.user'])),
        ]);
    }

    /**
     * Deactivate ownership.
     */
    public function deactivate(Ownership $ownership): JsonResponse
    {
        $this->authorize('deactivate', $ownership);

        $ownership = $this->ownershipService->deactivate($ownership);

        return response()->json([
            'success' => true,
            'message' => 'Ownership deactivated successfully.',
            'data' => new OwnershipResource($ownership->load(['createdBy', 'boardMembers.user', 'userMappings.user'])),
        ]);
    }

    /**
     * Switch active ownership (sets cookie).
     */
    public function switch(Request $request, Ownership $ownership): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this ownership
        if (!$user->isSuperAdmin() && !$user->hasOwnership($ownership->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this ownership.',
            ], 403);
        }

        // Set cookie using AuthService method for consistency
        $cookie = $this->authService->createOwnershipCookie($ownership->uuid);

        return response()->json([
            'success' => true,
            'message' => 'Ownership switched successfully.',
            'data' => [
                'ownership' => new OwnershipResource($ownership->load(['createdBy', 'boardMembers.user', 'userMappings.user'])),
                'current_ownership_uuid' => $ownership->uuid, // Include in response for immediate use
            ],
        ])->cookie($cookie);
    }
}
