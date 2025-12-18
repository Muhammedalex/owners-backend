<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ManageUserPermissionsRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Models\V1\Auth\User;
use App\Services\V1\Auth\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Get permissions info for a specific user:
     * - effective permissions (flat list)
     * - direct permissions (flat list)
     * - manageable permissions for the acting user (flat list)
     * - grouped permissions structure (similar to /permissions/grouped) annotated per user
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $actingUser = $request->user();
        $this->authorize('managePermissions', [User::class, $user]);

        // Effective permissions for target user
        $effective = $user->getAllPermissions()->pluck('name')->values();

        // Direct permissions (not via roles)
        $direct = $user->permissions->pluck('name')->values();

        // Permissions that acting user is allowed to give (intersection with all system permissions)
        $actingPermissions = $actingUser->getAllPermissions()->pluck('name')->values();
        $allPermissionNames = Permission::pluck('name');
        $manageable = $allPermissionNames->intersect($actingPermissions)->values();

        // Build grouped structure similar to /permissions/grouped but annotated for this user
        $grouped = $this->permissionService->getGroupedByModuleForUI(); // module => resource => action => permissionId

        // Preload permission names keyed by id for quick lookup
        $allIds = collect($grouped)
            ->flatMap(function ($module) {
                return collect($module)->flatMap(function ($resources) {
                    return array_values($resources);
                });
            })
            ->unique()
            ->values();

        $permissionsById = Permission::whereIn('id', $allIds)->get()->keyBy('id');

        $groupedWithFlags = [];

        foreach ($grouped as $module => $resources) {
            foreach ($resources as $resource => $actions) {
                foreach ($actions as $action => $permissionId) {
                    $permissionModel = $permissionsById->get($permissionId);
                    if (!$permissionModel) {
                        continue;
                    }

                    $permissionName = $permissionModel->name;

                    // لا نرجع إلا الصلاحيات التي يمكن للـ acting user إدارتها (manageable = true)
                    if (!$manageable->contains($permissionName)) {
                        continue;
                    }

                    $groupedWithFlags[$module][$resource][$action] = [
                        'id'         => $permissionId,
                        'name'       => $permissionName,
                        'effective'  => $effective->contains($permissionName),
                        'direct'     => $direct->contains($permissionName),
                        'manageable' => true,
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'permissions' => [
                    'effective' => $effective,
                    'direct' => $direct,
                    'manageable' => $manageable,
                    'grouped' => $groupedWithFlags,
                ],
            ],
        ]);
    }

    /**
     * Grant direct permissions to a user.
     */
    public function grant(ManageUserPermissionsRequest $request, User $user): JsonResponse
    {
        $actingUser = $request->user();
        $this->authorize('managePermissions', [User::class, $user]);

        $permissions = collect($request->input('permissions', []))
            ->filter()
            ->unique()
            ->values();

        // Ensure acting user owns all permissions he tries to grant
        $actingPermissions = $actingUser->getAllPermissions()->pluck('name')->values();
        $notOwned = $permissions->diff($actingPermissions);

        if ($notOwned->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot grant permissions you do not have.',
                'data' => [
                    'invalid_permissions' => $notOwned->values(),
                ],
            ], 403);
        }

        // Only grant existing permissions
        $validPermissions = Permission::whereIn('name', $permissions)->pluck('name');

        if ($validPermissions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid permissions to grant.',
            ], 422);
        }

        $user->givePermissionTo($validPermissions->all());

        return response()->json([
            'success' => true,
            'message' => 'Permissions granted successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Revoke direct permissions from a user.
     */
    public function revoke(ManageUserPermissionsRequest $request, User $user): JsonResponse
    {
        $actingUser = $request->user();
        $this->authorize('managePermissions', [User::class, $user]);

        $permissions = collect($request->input('permissions', []))
            ->filter()
            ->unique()
            ->values();

        // Only revoke existing permissions
        $validPermissions = Permission::whereIn('name', $permissions)->pluck('name');

        if ($validPermissions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid permissions to revoke.',
            ], 422);
        }

        $user->revokePermissionTo($validPermissions->all());

        return response()->json([
            'success' => true,
            'message' => 'Permissions revoked successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }
}

?>


