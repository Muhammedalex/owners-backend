<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\StoreUserRequest;
use App\Http\Requests\V1\Auth\SyncUserRolesRequest;
use App\Http\Requests\V1\Auth\UpdateUserRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Models\V1\Auth\User;
use App\Services\V1\Auth\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $currentUser = $request->user();
        $perPage = $request->input('per_page', 15);
        $filters = $request->only(['search', 'type', 'active', 'verified']);

        // If user has 'view.own' permission but not 'view', scope to current ownership
        if ($currentUser->can('auth.users.view.own') && !$currentUser->can('auth.users.view')) {
            $ownershipId = $request->input('current_ownership_id');
            if (!$ownershipId) {
                // User with view.own needs ownership scope
                return response()->json([
                    'success' => false,
                    'message' => 'Ownership scope required to view users.',
                ], 400);
            }
            $filters['ownership_id'] = $ownershipId;
        }

        $users = $this->userService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $currentUser = $request->user();
        $ownershipId = $request->input('current_ownership_id'); // From ownership.scope middleware

        $user = $this->userService->create(
            $request->validated(),
            $currentUser,
            $ownershipId
        );

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.' . ($currentUser && !$currentUser->isSuperAdmin() && $ownershipId ? ' User automatically linked to current ownership.' : ''),
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user = $this->userService->find($user->id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load(['roles', 'ownerships'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->userService->update($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Activate user.
     */
    public function activate(User $user): JsonResponse
    {
        $this->authorize('activate', $user);

        $user = $this->userService->activate($user);

        return response()->json([
            'success' => true,
            'message' => 'User activated successfully.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): JsonResponse
    {
        $this->authorize('deactivate', $user);

        $user = $this->userService->deactivate($user);

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Sync roles to user.
     */
    public function syncRoles(SyncUserRolesRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->userService->syncRoles($user, $request->validated()['roles']);

        return response()->json([
            'success' => true,
            'message' => 'Roles synced successfully.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Assign role to user.
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $request->validate([
            'role_name' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = $this->userService->assignRole($user, $request->input('role_name'));

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Remove role from user.
     */
    public function removeRole(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $request->validate([
            'role_name' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = $this->userService->removeRole($user, $request->input('role_name'));

        return response()->json([
            'success' => true,
            'message' => 'Role removed successfully.',
            'data' => new UserResource($user),
        ]);
    }
}

