<?php

namespace App\Http\Controllers\Api\V1\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Setting\StoreSystemSettingRequest;
use App\Http\Requests\V1\Setting\UpdateSystemSettingRequest;
use App\Http\Resources\V1\Setting\SystemSettingResource;
use App\Models\V1\Setting\SystemSetting;
use App\Services\V1\Setting\SystemSettingService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    use HasLocalizedResponse;
    public function __construct(
        private SystemSettingService $settingService
    ) {}

    /**
     * Display a listing of the resource.
     * 
     * GET /api/v1/settings
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $ownershipId = $request->input('current_ownership_id');

        // Determine scope
        $isSystemWide = $request->input('scope') === 'system';
        
        // Check permissions
        if ($isSystemWide) {
            // System-wide settings - Super Admin only
            if (!$user->isSuperAdmin()) {
                return $this->forbiddenResponse('settings.only_super_admin');
            }
            $filters = ['ownership_id' => 'system'];
        } else {
            // Ownership-specific settings
            if (!$ownershipId) {
                return $this->errorResponse('settings.ownership_scope_required', 400);
            }
            $filters = ['ownership_id' => $ownershipId];
        }

        // Apply additional filters
        if ($request->has('group')) {
            $filters['group'] = $request->input('group');
        }

        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }

        if ($request->has('value_type')) {
            $filters['value_type'] = $request->input('value_type');
        }

        $perPage = $request->input('per_page', 15);
        $settings = $this->settingService->paginate($perPage, $filters);

        return $this->successResponse(
            SystemSettingResource::collection($settings->items()),
            null,
            200,
            [
                'current_page' => $settings->currentPage(),
                'last_page' => $settings->lastPage(),
                'per_page' => $settings->perPage(),
                'total' => $settings->total(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     * 
     * POST /api/v1/settings
     */
    public function store(StoreSystemSettingRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Determine if system-wide or ownership-specific
        $isSystemWide = !isset($data['ownership_id']) || $data['ownership_id'] === null;

        // Check permissions based on group
        $group = $data['group'];
        $permission = $isSystemWide 
            ? "settings.system.update"
            : "settings.{$group}.update";

        if (!$user->can($permission)) {
            return $this->forbiddenResponse(__('settings.permission_denied', ['group' => $group]));
        }

        // For ownership-specific settings, use current ownership
        if (!$isSystemWide) {
            $ownershipId = $request->input('current_ownership_id');
            if (!$ownershipId) {
                return $this->errorResponse('messages.errors.ownership_required', 400);
            }
            $data['ownership_id'] = $ownershipId;
        }

        $setting = $this->settingService->create($data);

        return $this->successResponse(
            new SystemSettingResource($setting->load('ownership')),
            'settings.created',
            201
        );
    }

    /**
     * Display the specified resource.
     * 
     * GET /api/v1/settings/{setting}
     */
    public function show(Request $request, int $setting): JsonResponse
    {
        $user = $request->user();
        $setting = $this->settingService->find($setting);

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found.',
            ], 404);
        }

        // Check permissions
        if ($setting->isSystemWide()) {
            if (!$user->can('settings.system.view')) {
                return $this->forbiddenResponse('settings.only_super_admin');
            }
        } else {
            $permission = "settings.{$setting->group}.view";
            if (!$user->can($permission)) {
                return $this->forbiddenResponse(__('settings.permission_denied', ['group' => $setting->group]));
            }

            // Check ownership access
            $ownershipId = $request->input('current_ownership_id');
            if (!$user->isSuperAdmin() && $setting->ownership_id != $ownershipId) {
                return $this->forbiddenResponse('messages.errors.no_access');
            }
        }

        return $this->successResponse(new SystemSettingResource($setting->load('ownership')));
    }

    /**
     * Update the specified resource in storage.
     * 
     * PUT /api/v1/settings/{setting}
     */
    public function update(UpdateSystemSettingRequest $request, int $setting): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        
        $setting = $this->settingService->find($setting);

        if (!$setting) {
            return $this->notFoundResponse('settings.not_found');
        }

        // Check permissions
        if ($setting->isSystemWide()) {
            if (!$user->can('settings.system.update')) {
                return $this->forbiddenResponse('settings.only_super_admin');
            }
        } else {
            $permission = "settings.{$setting->group}.update";
            if (!$user->can($permission)) {
                return $this->forbiddenResponse(__('settings.permission_denied', ['group' => $setting->group]));
            }

            // Check ownership access
            $ownershipId = $request->input('current_ownership_id');
            if (!$user->isSuperAdmin() && $setting->ownership_id != $ownershipId) {
                return $this->forbiddenResponse('messages.errors.no_access');
            }
        }

        $setting = $this->settingService->update($setting, $data);

        return $this->successResponse(
            new SystemSettingResource($setting->load('ownership')),
            'settings.updated'
        );
    }

    /**
     * Remove the specified resource from storage.
     * 
     * DELETE /api/v1/settings/{setting}
     */
    public function destroy(Request $request, int $setting): JsonResponse
    {
        $user = $request->user();
        
        $setting = $this->settingService->find($setting);

        if (!$setting) {
            return $this->notFoundResponse('settings.not_found');
        }

        // Check permissions
        if ($setting->isSystemWide()) {
            if (!$user->can('settings.system.update')) {
                return $this->forbiddenResponse('settings.only_super_admin');
            }
        } else {
            $permission = "settings.{$setting->group}.update";
            if (!$user->can($permission)) {
                return $this->forbiddenResponse(__('settings.permission_denied', ['group' => $setting->group]));
            }

            // Check ownership access
            $ownershipId = $request->input('current_ownership_id');
            if (!$user->isSuperAdmin() && $setting->ownership_id != $ownershipId) {
                return $this->forbiddenResponse('messages.errors.no_access');
            }
        }

        $this->settingService->delete($setting);

        return $this->successResponse(null, 'settings.deleted');
    }

    /**
     * Get settings by group.
     * 
     * GET /api/v1/settings/group/{group}
     */
    public function getByGroup(Request $request, string $group): JsonResponse
    {
        $user = $request->user();
        $ownershipId = $request->input('current_ownership_id');

        // Check permissions
        $permission = "settings.{$group}.view";
        if (!$user->can($permission)) {
            return $this->forbiddenResponse(__('settings.permission_denied', ['group' => $group]));
        }

        // For ownership-specific settings
        if (!$user->isSuperAdmin() && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $settings = $this->settingService->getByGroup($group, $ownershipId);

        return $this->successResponse(SystemSettingResource::collection($settings));
    }

    /**
     * Get setting value by key.
     * 
     * GET /api/v1/settings/key/{key}
     */
    public function getByKey(Request $request, string $key): JsonResponse
    {
        $user = $request->user();
        $ownershipId = $request->input('current_ownership_id');

        $setting = $this->settingService->findByKey($key, $ownershipId);

        if (!$setting) {
            return $this->notFoundResponse('settings.not_found');
        }

        // Check permissions
        if ($setting->isSystemWide()) {
            if (!$user->can('settings.system.view')) {
                return $this->forbiddenResponse('settings.only_super_admin');
            }
        } else {
            $permission = "settings.{$setting->group}.view";
            if (!$user->can($permission)) {
                return $this->forbiddenResponse(__('settings.permission_denied', ['group' => $setting->group]));
            }

            // Check ownership access
            if (!$user->isSuperAdmin() && $setting->ownership_id != $ownershipId) {
                return $this->forbiddenResponse('messages.errors.no_access');
            }
        }

        return $this->successResponse(new SystemSettingResource($setting->load('ownership')));
    }

    /**
     * Get all settings for current ownership (with system defaults).
     * 
     * GET /api/v1/settings/all
     */
    public function getAll(Request $request): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $settings = $this->settingService->getAllForOwnership($ownershipId);

        return $this->successResponse(SystemSettingResource::collection($settings));
    }

    /**
     * Bulk update settings.
     * 
     * PUT /api/v1/settings/bulk
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $user = $request->user();
        $ownershipId = $request->input('current_ownership_id');

        $validated = $request->validate([
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['nullable'],
            'settings.*.value_type' => ['required', 'string', 'in:string,integer,decimal,boolean,json,array'],
            'settings.*.group' => ['required', 'string', 'max:50'],
            'settings.*.description' => ['nullable', 'string'],
        ]);

        // Check permissions for each group
        $groups = collect($validated['settings'])->pluck('group')->unique();
        foreach ($groups as $group) {
            $permission = "settings.{$group}.update";
            if (!$user->can($permission)) {
                return $this->forbiddenResponse(__('settings.permission_denied', ['group' => $group]));
            }
        }

        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $settings = $this->settingService->bulkUpdate($validated['settings'], $ownershipId);

        return $this->successResponse(
            SystemSettingResource::collection($settings),
            'messages.success.updated'
        );
    }
}

