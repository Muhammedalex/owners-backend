<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\StoreBuildingFloorRequest;
use App\Http\Requests\V1\Ownership\UpdateBuildingFloorRequest;
use App\Http\Resources\V1\Ownership\BuildingFloorResource;
use App\Models\V1\Ownership\BuildingFloor;
use App\Services\V1\Ownership\BuildingFloorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingFloorController extends Controller
{
    public function __construct(
        private BuildingFloorService $buildingFloorService
    ) {}

    /**
     * Display a listing of the resource.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BuildingFloor::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope is required.',
            ], 400);
        }

        $perPage = $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['building_id', 'search', 'active'])
        );

        $floors = $this->buildingFloorService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => BuildingFloorResource::collection($floors->items()),
            'meta' => [
                'current_page' => $floors->currentPage(),
                'last_page' => $floors->lastPage(),
                'per_page' => $floors->perPage(),
                'total' => $floors->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StoreBuildingFloorRequest $request): JsonResponse
    {
        $this->authorize('create', BuildingFloor::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope is required.',
            ], 400);
        }

        $floor = $this->buildingFloorService->create($request->validated(), $ownershipId);

        return response()->json([
            'success' => true,
            'message' => 'Building floor created successfully.',
            'data' => new BuildingFloorResource($floor->load(['building.ownership', 'building.portfolio'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, BuildingFloor $buildingFloor): JsonResponse
    {
        $this->authorize('view', $buildingFloor);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope is required.',
            ], 400);
        }

        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building floor not found or access denied.',
            ], 404);
        }

        $floor = $this->buildingFloorService->find($buildingFloor->id);

        if (!$floor) {
            return response()->json([
                'success' => false,
                'message' => 'Building floor not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new BuildingFloorResource($floor->load(['building.ownership', 'building.portfolio'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdateBuildingFloorRequest $request, BuildingFloor $buildingFloor): JsonResponse
    {
        $this->authorize('update', $buildingFloor);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope is required.',
            ], 400);
        }

        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building floor not found or access denied.',
            ], 404);
        }

        $floor = $this->buildingFloorService->update($buildingFloor, $request->validated(), $ownershipId);

        return response()->json([
            'success' => true,
            'message' => 'Building floor updated successfully.',
            'data' => new BuildingFloorResource($floor->load(['building.ownership', 'building.portfolio'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * Ownership scope is mandatory from middleware.
     */
    public function destroy(Request $request, BuildingFloor $buildingFloor): JsonResponse
    {
        $this->authorize('delete', $buildingFloor);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope is required.',
            ], 400);
        }

        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building floor not found or access denied.',
            ], 404);
        }

        $this->buildingFloorService->delete($buildingFloor);

        return response()->json([
            'success' => true,
            'message' => 'Building floor deleted successfully.',
        ]);
    }

    /**
     * Activate building floor.
     * Ownership scope is mandatory from middleware.
     */
    public function activate(Request $request, BuildingFloor $buildingFloor): JsonResponse
    {
        $this->authorize('activate', $buildingFloor);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope is required.',
            ], 400);
        }

        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building floor not found or access denied.',
            ], 404);
        }

        $floor = $this->buildingFloorService->activate($buildingFloor);

        return response()->json([
            'success' => true,
            'message' => 'Building floor activated successfully.',
            'data' => new BuildingFloorResource($floor->load(['building.ownership', 'building.portfolio'])),
        ]);
    }

    /**
     * Deactivate building floor.
     * Ownership scope is mandatory from middleware.
     */
    public function deactivate(Request $request, BuildingFloor $buildingFloor): JsonResponse
    {
        $this->authorize('deactivate', $buildingFloor);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope is required.',
            ], 400);
        }

        $building = $buildingFloor->building;
        if (!$building || $building->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Building floor not found or access denied.',
            ], 404);
        }

        $floor = $this->buildingFloorService->deactivate($buildingFloor);

        return response()->json([
            'success' => true,
            'message' => 'Building floor deactivated successfully.',
            'data' => new BuildingFloorResource($floor->load(['building.ownership', 'building.portfolio'])),
        ]);
    }
}
