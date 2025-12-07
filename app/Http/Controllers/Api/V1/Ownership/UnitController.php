<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\StoreUnitRequest;
use App\Http\Requests\V1\Ownership\UpdateUnitRequest;
use App\Http\Resources\V1\Ownership\UnitResource;
use App\Models\V1\Ownership\Unit;
use App\Models\V1\Ownership\UnitSpecification;
use App\Services\V1\Ownership\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(
        private UnitService $unitService
    ) {}

    /**
     * Display a listing of the resource.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

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
            $request->only(['search', 'type', 'status', 'building_id', 'floor_id', 'active'])
        );

        $units = $this->unitService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => UnitResource::collection($units->items()),
            'meta' => [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $this->authorize('create', Unit::class);

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

        // Handle specifications separately
        $specifications = $data['specifications'] ?? [];
        unset($data['specifications']);

        $unit = $this->unitService->create($data);

        // Create specifications if provided
        if (!empty($specifications)) {
            foreach ($specifications as $spec) {
                UnitSpecification::create([
                    'unit_id' => $unit->id,
                    'key' => $spec['key'],
                    'value' => $spec['value'] ?? null,
                    'type' => $spec['type'] ?? null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully.',
            'data' => new UnitResource($unit->fresh()->load(['ownership', 'building.portfolio', 'floor', 'specifications'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('view', $unit);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $unit->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found or access denied.',
            ], 404);
        }

        $unit = $this->unitService->findByUuid($unit->uuid);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UnitResource($unit->load(['ownership', 'building.portfolio', 'floor', 'specifications'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $this->authorize('update', $unit);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $unit->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found or access denied.',
            ], 404);
        }

        $data = $request->validated();

        // Handle specifications separately
        $specifications = $data['specifications'] ?? null;
        unset($data['specifications']);

        $unit = $this->unitService->update($unit, $data);

        // Update specifications if provided
        if ($specifications !== null) {
            // Delete existing specifications
            $unit->specifications()->delete();

            // Create new specifications
            if (!empty($specifications)) {
                foreach ($specifications as $spec) {
                    UnitSpecification::create([
                        'unit_id' => $unit->id,
                        'key' => $spec['key'],
                        'value' => $spec['value'] ?? null,
                        'type' => $spec['type'] ?? null,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully.',
            'data' => new UnitResource($unit->fresh()->load(['ownership', 'building.portfolio', 'floor', 'specifications'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * Ownership scope is mandatory from middleware.
     */
    public function destroy(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('delete', $unit);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $unit->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found or access denied.',
            ], 404);
        }

        $this->unitService->delete($unit);

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully.',
        ]);
    }

    /**
     * Activate unit.
     * Ownership scope is mandatory from middleware.
     */
    public function activate(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('activate', $unit);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $unit->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found or access denied.',
            ], 404);
        }

        $unit = $this->unitService->activate($unit);

        return response()->json([
            'success' => true,
            'message' => 'Unit activated successfully.',
            'data' => new UnitResource($unit->load(['ownership', 'building.portfolio', 'floor', 'specifications'])),
        ]);
    }

    /**
     * Deactivate unit.
     * Ownership scope is mandatory from middleware.
     */
    public function deactivate(Request $request, Unit $unit): JsonResponse
    {
        $this->authorize('deactivate', $unit);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $unit->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Unit not found or access denied.',
            ], 404);
        }

        $unit = $this->unitService->deactivate($unit);

        return response()->json([
            'success' => true,
            'message' => 'Unit deactivated successfully.',
            'data' => new UnitResource($unit->load(['ownership', 'building.portfolio', 'floor', 'specifications'])),
        ]);
    }
}
