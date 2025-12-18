<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\StoreUnitRequest;
use App\Http\Requests\V1\Ownership\UpdateUnitRequest;
use App\Http\Resources\V1\Ownership\UnitResource;
use App\Models\V1\Ownership\Unit;
use App\Models\V1\Ownership\UnitSpecification;
use App\Services\V1\Ownership\UnitService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use HasLocalizedResponse;
    
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
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['search', 'type', 'status', 'building_id', 'floor_id', 'active'])
        );

        if ($perPage === -1) {
            $units = $this->unitService->all($filters);

            return $this->successResponse(
                UnitResource::collection($units)
            );
        }

        $units = $this->unitService->paginate($perPage, $filters);

        return $this->successResponse(
            UnitResource::collection($units->items()),
            null,
            200,
            [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
            ]
        );
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
            return $this->errorResponse('messages.errors.ownership_required', 400);
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

        return $this->successResponse(
            new UnitResource($unit->fresh()->load(['ownership', 'building.portfolio', 'floor', 'specifications'])),
            'units.created',
            201
        );
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
            return $this->notFoundResponse('units.not_found');
        }

        $unit = $this->unitService->findByUuid($unit->uuid);

        if (!$unit) {
            return $this->notFoundResponse('units.not_found');
        }

        return $this->successResponse(
            new UnitResource($unit->load(['ownership', 'building.portfolio', 'floor', 'specifications']))
        );
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
            return $this->notFoundResponse('units.not_found');
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

        return $this->successResponse(
            new UnitResource($unit->fresh()->load(['ownership', 'building.portfolio', 'floor', 'specifications'])),
            'units.updated'
        );
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
            return $this->notFoundResponse('units.not_found');
        }

        $this->unitService->delete($unit);

        return $this->successResponse(null, 'units.deleted');
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
            return $this->notFoundResponse('units.not_found');
        }

        $unit = $this->unitService->activate($unit);

        return $this->successResponse(
            new UnitResource($unit->load(['ownership', 'building.portfolio', 'floor', 'specifications'])),
            'units.activated'
        );
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
            return $this->notFoundResponse('units.not_found');
        }

        $unit = $this->unitService->deactivate($unit);

        return $this->successResponse(
            new UnitResource($unit->load(['ownership', 'building.portfolio', 'floor', 'specifications'])),
            'units.deactivated'
        );
    }
}
