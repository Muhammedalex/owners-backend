<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\AssignUserToOwnershipRequest;
use App\Http\Resources\V1\Ownership\UserOwnershipMappingResource;
use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Services\V1\Ownership\UserOwnershipMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserOwnershipMappingController extends Controller
{
    public function __construct(
        private UserOwnershipMappingService $mappingService
    ) {}

    /**
     * Get user's ownerships.
     */
    public function getUserOwnerships(User $user): JsonResponse
    {
        $requestUser = request()->user();

        // Users can view their own ownerships, or Super Admin can view any
        if ($requestUser->id !== $user->id && !$requestUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $mappings = $this->mappingService->getByUser($user->id);

        return response()->json([
            'success' => true,
            'data' => UserOwnershipMappingResource::collection($mappings),
        ]);
    }

    /**
     * Get ownership's users.
     */
    public function getOwnershipUsers(Request $request): JsonResponse
    {
        // Get ownership from cookie scope (set by middleware)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope not found.',
            ], 400);
        }

        $mappings = $this->mappingService->getByOwnership($ownershipId);

        return response()->json([
            'success' => true,
            'data' => UserOwnershipMappingResource::collection($mappings),
        ]);
    }

    /**
     * Assign user to ownership.
     */
    public function assign(AssignUserToOwnershipRequest $request): JsonResponse
    {
        $currentUser = $request->user();

        // Only Super Admin can use this endpoint to link users later
        if (!$currentUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only Super Admin can assign users to ownerships. Regular owners should create users directly, which auto-links them.',
            ], 403);
        }

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
            $mapping = $this->mappingService->create($data);

            return response()->json([
                'success' => true,
                'message' => 'User assigned to ownership successfully.',
                'data' => new UserOwnershipMappingResource($mapping->load(['user', 'ownership'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove user from ownership.
     */
    public function remove(Request $request, User $user): JsonResponse
    {
        // Get ownership from cookie scope (set by middleware)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership scope not found.',
            ], 400);
        }

        $mapping = $this->mappingService->findByUserAndOwnership($user->id, $ownershipId);

        if (!$mapping) {
            return response()->json([
                'success' => false,
                'message' => 'User is not mapped to this ownership.',
            ], 404);
        }

        $this->mappingService->delete($mapping);

        return response()->json([
            'success' => true,
            'message' => 'User removed from ownership successfully.',
        ]);
    }

    /**
     * Set default ownership for user.
     */
    public function setDefault(User $user, Ownership $ownership): JsonResponse
    {
        $requestUser = request()->user();

        // Users can set their own default, or Super Admin can set for any user
        if ($requestUser->id !== $user->id && !$requestUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $mapping = $this->mappingService->findByUserAndOwnership($user->id, $ownership->id);

        if (!$mapping) {
            return response()->json([
                'success' => false,
                'message' => 'User is not mapped to this ownership.',
            ], 404);
        }

        $mapping = $this->mappingService->setAsDefault($mapping);

        return response()->json([
            'success' => true,
            'message' => 'Default ownership set successfully.',
            'data' => new UserOwnershipMappingResource($mapping->load(['user', 'ownership'])),
        ]);
    }
}
