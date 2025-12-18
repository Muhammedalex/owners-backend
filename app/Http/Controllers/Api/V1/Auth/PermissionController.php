<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Auth\PermissionResource;
use App\Models\V1\Auth\Permission;
use App\Services\V1\Auth\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Permission Controller - Read Only
 * 
 * Permissions are hard-coded in seeders and cannot be created/updated/deleted via API.
 * Only viewing and grouping operations are allowed.
 */
class PermissionController extends Controller
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['search', 'guard_name', 'module']);

        if ($perPage === -1) {
            $permissions = $this->permissionService->all($filters);

            return response()->json([
                'success' => true,
                'data' => PermissionResource::collection($permissions),
            ]);
        }

        $permissions = $this->permissionService->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => PermissionResource::collection($permissions->items()),
            'meta' => [
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission): JsonResponse
    {
        $this->authorize('view', $permission);

        $permission = $this->permissionService->find($permission->id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PermissionResource($permission),
        ]);
    }

    /**
     * Get permissions grouped by module in UI-friendly format.
     * 
     * Returns permissions structured for frontend use with permission IDs for role sync.
     * Format: { module: { resource: { action: permissionId } } }
     */
    public function groupedByModule(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $grouped = $this->permissionService->getGroupedByModuleForUI();

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }
}

