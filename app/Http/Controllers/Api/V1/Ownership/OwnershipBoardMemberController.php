<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\StoreOwnershipBoardMemberRequest;
use App\Http\Requests\V1\Ownership\UpdateOwnershipBoardMemberRequest;
use App\Http\Resources\V1\Ownership\OwnershipBoardMemberResource;
use App\Models\V1\Ownership\OwnershipBoardMember;
use App\Services\V1\Ownership\OwnershipBoardMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnershipBoardMemberController extends Controller
{
    public function __construct(
        private OwnershipBoardMemberService $boardMemberService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OwnershipBoardMember::class);

        // Get ownership from cookie scope (set by middleware)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope not found.',
            ], 400);
        }

        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId],
            $request->only(['user_id', 'role', 'active'])
        );

        if ($perPage === -1) {
            $boardMembers = $this->boardMemberService->all($filters);

            return response()->json([
                'success' => true,
                'data' => OwnershipBoardMemberResource::collection($boardMembers),
            ]);
        }

        $boardMembers = $this->boardMemberService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => OwnershipBoardMemberResource::collection($boardMembers->items()),
            'meta' => [
                'current_page' => $boardMembers->currentPage(),
                'last_page' => $boardMembers->lastPage(),
                'per_page' => $boardMembers->perPage(),
                'total' => $boardMembers->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOwnershipBoardMemberRequest $request): JsonResponse
    {
        // Get ownership from cookie scope (set by middleware)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope not found.',
            ], 400);
        }

        $data = $request->validated();
        $data['ownership_id'] = $ownershipId;

        try {
            $boardMember = $this->boardMemberService->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Board member added successfully.',
                'data' => new OwnershipBoardMemberResource($boardMember->load(['ownership', 'user'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, OwnershipBoardMember $boardMember): JsonResponse
    {
        $this->authorize('view', $boardMember);

        // Get ownership from cookie scope (set by middleware)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope not found.',
            ], 400);
        }

        // Verify board member belongs to ownership
        if ($boardMember->ownership_id !== $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Board member not found for this ownership.',
            ], 404);
        }

        $boardMember = $this->boardMemberService->find($boardMember->id);

        if (!$boardMember) {
            return response()->json([
                'success' => false,
                'message' => 'Board member not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OwnershipBoardMemberResource($boardMember->load(['ownership', 'user'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOwnershipBoardMemberRequest $request, OwnershipBoardMember $boardMember): JsonResponse
    {
        // Get ownership from cookie scope (set by middleware)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope not found.',
            ], 400);
        }

        // Verify board member belongs to ownership
        if ($boardMember->ownership_id !== $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Board member not found for this ownership.',
            ], 404);
        }

        $boardMember = $this->boardMemberService->update($boardMember, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Board member updated successfully.',
            'data' => new OwnershipBoardMemberResource($boardMember->load(['ownership', 'user'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, OwnershipBoardMember $boardMember): JsonResponse
    {
        $this->authorize('delete', $boardMember);

        // Get ownership from cookie scope (set by middleware)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope not found.',
            ], 400);
        }

        // Verify board member belongs to ownership
        if ($boardMember->ownership_id !== $ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Board member not found for this ownership.',
            ], 404);
        }

        $this->boardMemberService->delete($boardMember);

        return response()->json([
            'success' => true,
            'message' => 'Board member removed successfully.',
        ]);
    }
}
