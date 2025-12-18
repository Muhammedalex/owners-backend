<?php

namespace App\Http\Resources\V1\Auth;

use App\Models\Scopes\ExcludeSystemRolesScope;
use App\Models\V1\Auth\User;
use App\Services\V1\Auth\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $permissionService = app(PermissionService::class);

        // Super Admin: تجاوز السكوب دائماً وإرجاع كل الصلاحيات
        if ($this->resource instanceof User && $this->resource->isSuperAdmin()) {
            $permissionNames = Permission::pluck('name');
        } else {
            // Get permission names only (not full objects)
            $permissionNames = $this->getAllPermissions()
                ->map(fn($permission) => $permission->name)
                ->filter()
                ->values();
        }

        $uiPermissions = $permissionService->transformToUIPermissions($permissionNames);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'name' => $this->name,
            'first' => $this->first,
            'last' => $this->last,
            'company' => $this->company,
            'avatar' => $this->avatar,
            'type' => $this->type,
            'active' => $this->active,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'timezone' => $this->timezone,
            'locale' => $this->locale,

            // للأدوار: لو المستخدم Super Admin نتجاوز الـ global scope
            'roles' => $this->when(true, function () {
                if ($this->resource instanceof User && $this->resource->isSuperAdmin()) {
                    return $this->roles()
                        ->withoutGlobalScope(ExcludeSystemRolesScope::class)
                        ->pluck('name');
                }

                if ($this->relationLoaded('roles')) {
                    return $this->roles->pluck('name');
                }

                return $this->roles()->pluck('name');
            }),
            'ownerships' => $this->whenLoaded('ownerships', function () {
                return $this->ownerships->map(function ($ownership) {
                    return [
                        'uuid' => $ownership->uuid,
                        'name' => $ownership->name,
                        'default' => $ownership->pivot->default ?? false,
                    ];
                });
            }),
            'ui' => [
                'permissions' => $uiPermissions,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

