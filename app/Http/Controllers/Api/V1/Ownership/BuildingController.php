<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\StoreBuildingRequest;
use App\Http\Requests\V1\Ownership\UpdateBuildingRequest;
use App\Http\Resources\V1\Ownership\BuildingResource;
use App\Models\V1\Ownership\Building;
use App\Services\V1\Ownership\BuildingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function __construct(
        private BuildingService $buildingService
    ) {}

    /**
     * Display a listing of the resource.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Building::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope is required.',
            ], 400);
        }

        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['search', 'type', 'portfolio_id', 'parent_id', 'city', 'active'])
        );

        if ($perPage === -1) {
            $buildings = $this->buildingService->all($filters);

            return response()->json([
                'success' => true,
                'data' => BuildingResource::collection($buildings),
            ]);
        }

        $buildings = $this->buildingService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => BuildingResource::collection($buildings->items()),
            'meta' => [
                'current_page' => $buildings->currentPage(),
                'last_page' => $buildings->lastPage(),
                'per_page' => $buildings->perPage(),
                'total' => $buildings->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StoreBuildingRequest $request): JsonResponse
    {
        $this->authorize('create', Building::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope is required.',
            ], 400);
        }

        $data = $request->validated();
        $data['ownership_id'] = $ownershipId; // MANDATORY

        $building = $this->buildingService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Building created successfully.',
            'data' => new BuildingResource($building->load(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, Building $building): JsonResponse
    {
        $this->authorize('view', $building);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found or access denied.',
            ], 404);
        }

        $building = $this->buildingService->findByUuid($building->uuid);

        if (!$building) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new BuildingResource($building->load(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdateBuildingRequest $request, Building $building): JsonResponse
    {
        $this->authorize('update', $building);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found or access denied.',
            ], 404);
        }

        $building = $this->buildingService->update($building, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Building updated successfully.',
            'data' => new BuildingResource($building->load(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * Ownership scope is mandatory from middleware.
     */
    public function destroy(Request $request, Building $building): JsonResponse
    {
        $this->authorize('delete', $building);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found or access denied.',
            ], 404);
        }

        $this->buildingService->delete($building);

        return response()->json([
            'success' => true,
            'message' => 'Building deleted successfully.',
        ]);
    }

    /**
     * Activate building.
     * Ownership scope is mandatory from middleware.
     */
    public function activate(Request $request, Building $building): JsonResponse
    {
        $this->authorize('activate', $building);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found or access denied.',
            ], 404);
        }

        $building = $this->buildingService->activate($building);

        return response()->json([
            'success' => true,
            'message' => 'Building activated successfully.',
            'data' => new BuildingResource($building->load(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])),
        ]);
    }

    /**
     * Deactivate building.
     * Ownership scope is mandatory from middleware.
     */
    public function deactivate(Request $request, Building $building): JsonResponse
    {
        $this->authorize('deactivate', $building);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found or access denied.',
            ], 404);
        }

        $building = $this->buildingService->deactivate($building);

        return response()->json([
            'success' => true,
            'message' => 'Building deactivated successfully.',
            'data' => new BuildingResource($building->load(['ownership', 'portfolio', 'parent', 'children', 'buildingFloors'])),
        ]);
    }
}
