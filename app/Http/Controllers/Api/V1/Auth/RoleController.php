<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\StoreRoleRequest;
use App\Http\Requests\V1\Auth\SyncRolePermissionsRequest;
use App\Http\Requests\V1\Auth\UpdateRoleRequest;
use App\Http\Resources\V1\Auth\RoleResource;
use App\Models\V1\Auth\Role;
use App\Services\V1\Auth\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $filters = $request->only(['search', 'guard_name']);

        $roles = $this->roleService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => RoleResource::collection($roles->items()),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => new RoleResource($role),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): JsonResponse
    {
        $role = $this->roleService->find($role->id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $role = $this->roleService->update($role, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->roleService->delete($role);

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
        ]);
    }

    /**
     * Sync permissions to role.
     */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $role = $this->roleService->syncPermissions($role, $request->validated()['permissions']);

        return response()->json([
            'success' => true,
            'message' => 'Permissions synced successfully.',
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Give permission to role.
     */
    public function givePermission(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        $role = $this->roleService->givePermissionTo($role, $request->input('permission_id'));

        return response()->json([
            'success' => true,
            'message' => 'Permission granted successfully.',
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Revoke permission from role.
     */
    public function revokePermission(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        $role = $this->roleService->revokePermissionTo($role, $request->input('permission_id'));

        return response()->json([
            'success' => true,
            'message' => 'Permission revoked successfully.',
            'data' => new RoleResource($role),
        ]);
    }
}

