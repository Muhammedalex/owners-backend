<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\AssignUserToOwnershipRequest;
use App\Http\Resources\V1\Ownership\UserOwnershipMappingResource;
use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Policies\V1\Ownership\UserOwnershipMappingPolicy;
use App\Services\V1\Ownership\UserOwnershipMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserOwnershipMappingController extends Controller
{
    public function __construct(
        private UserOwnershipMappingService $mappingService,
        private UserOwnershipMappingPolicy $policy
    ) {}

    /**
     * Get user's ownerships.
     */
    public function getUserOwnerships(User $user): JsonResponse
    {
        $requestUser = request()->user();
        if (!$this->policy->viewUserOwnerships($requestUser, $user)) {
            abort(403, 'Unauthorized.');
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

        $ownership = Ownership::findOrFail($ownershipId);
        $requestUser = request()->user();
        if (!$this->policy->viewOwnershipUsers($requestUser, $ownership)) {
            abort(403, 'Unauthorized.');
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
        $isSuperAdmin = $currentUser->isSuperAdmin();

        $data = $request->validated();
        
        // Determine ownership_id:
        // 1. If Super Admin: must provide ownership_uuid or ownership_id in request
        // 2. If non-Super Admin: get from middleware (current_ownership_id)
        if ($isSuperAdmin) {
            // Super Admin must provide ownership_id or ownership_uuid
            if (!isset($data['ownership_id']) || empty($data['ownership_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ownership ID or UUID is required for Super Admin.',
                ], 400);
            }
            $ownershipId = $data['ownership_id'];
        } else {
            // Non-Super Admin: get ownership_id from middleware
            $ownershipId = $request->input('current_ownership_id');
            if (!$ownershipId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ownership scope not found.',
                ], 400);
            }
            // Set ownership_id in data
            $data['ownership_id'] = $ownershipId;
        }

        // Get ownership for authorization
        $ownership = Ownership::findOrFail($ownershipId);
        if (!$this->policy->assign($currentUser, $ownership)) {
            abort(403, 'Unauthorized.');
        }

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

        $ownership = Ownership::findOrFail($ownershipId);
        $requestUser = request()->user();
        if (!$this->policy->remove($requestUser, $ownership)) {
            abort(403, 'Unauthorized.');
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
        if (!$this->policy->setDefault($requestUser, $user, $ownership)) {
            abort(403, 'Unauthorized.');
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
