<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\StorePortfolioRequest;
use App\Http\Requests\V1\Ownership\UpdatePortfolioRequest;
use App\Http\Resources\V1\Ownership\PortfolioResource;
use App\Models\V1\Ownership\Portfolio;
use App\Services\V1\Ownership\PortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct(
        private PortfolioService $portfolioService
    ) {}

    /**
     * Display a listing of the resource.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Portfolio::class);

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
            $request->only(['search', 'type', 'parent_id', 'active'])
        );

        if ($perPage === -1) {
            $portfolios = $this->portfolioService->all($filters);

            return response()->json([
                'success' => true,
                'data' => PortfolioResource::collection($portfolios),
            ]);
        }

        $portfolios = $this->portfolioService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => PortfolioResource::collection($portfolios->items()),
            'meta' => [
                'current_page' => $portfolios->currentPage(),
                'last_page' => $portfolios->lastPage(),
                'per_page' => $portfolios->perPage(),
                'total' => $portfolios->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function store(StorePortfolioRequest $request): JsonResponse
    {
        $this->authorize('create', Portfolio::class);

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

        $portfolio = $this->portfolioService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Portfolio created successfully.',
            'data' => new PortfolioResource($portfolio->load(['ownership', 'parent', 'children', 'locations', 'buildings'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('view', $portfolio);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $portfolio->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Portfolio not found or access denied.',
            ], 404);
        }

        $portfolio = $this->portfolioService->findByUuid($portfolio->uuid);

        if (!$portfolio) {
            return response()->json([
                'success' => false,
                'message' => 'Portfolio not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PortfolioResource($portfolio->load(['ownership', 'parent', 'children', 'locations', 'buildings'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     * Ownership scope is mandatory from middleware.
     */
    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('update', $portfolio);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $portfolio->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Portfolio not found or access denied.',
            ], 404);
        }

        $portfolio = $this->portfolioService->update($portfolio, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Portfolio updated successfully.',
            'data' => new PortfolioResource($portfolio->load(['ownership', 'parent', 'children', 'locations', 'buildings'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * Ownership scope is mandatory from middleware.
     */
    public function destroy(Request $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('delete', $portfolio);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $portfolio->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Portfolio not found or access denied.',
            ], 404);
        }

        $this->portfolioService->delete($portfolio);

        return response()->json([
            'success' => true,
            'message' => 'Portfolio deleted successfully.',
        ]);
    }

    /**
     * Activate portfolio.
     * Ownership scope is mandatory from middleware.
     */
    public function activate(Request $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('activate', $portfolio);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $portfolio->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Portfolio not found or access denied.',
            ], 404);
        }

        $portfolio = $this->portfolioService->activate($portfolio);

        return response()->json([
            'success' => true,
            'message' => 'Portfolio activated successfully.',
            'data' => new PortfolioResource($portfolio->load(['ownership', 'parent', 'children', 'locations', 'buildings'])),
        ]);
    }

    /**
     * Deactivate portfolio.
     * Ownership scope is mandatory from middleware.
     */
    public function deactivate(Request $request, Portfolio $portfolio): JsonResponse
    {
        $this->authorize('deactivate', $portfolio);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $portfolio->ownership_id != $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Portfolio not found or access denied.',
            ], 404);
        }

        $portfolio = $this->portfolioService->deactivate($portfolio);

        return response()->json([
            'success' => true,
            'message' => 'Portfolio deactivated successfully.',
            'data' => new PortfolioResource($portfolio->load(['ownership', 'parent', 'children', 'locations', 'buildings'])),
        ]);
    }
}
