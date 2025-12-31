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
            'email_verified' => $this->hasVerifiedEmail(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'is_collector' => $this->isCollector(),
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
                        'legal' => $ownership->legal,
                        'type' => $ownership->type,
                        'ownership_type' => $ownership->ownership_type,
                        'registration' => $ownership->registration,
                        'tax_id' => $ownership->tax_id,
                        'street' => $ownership->street,
                        'city' => $ownership->city,
                        'state' => $ownership->state,
                        'country' => $ownership->country,
                        'zip_code' => $ownership->zip_code,
                        'email' => $ownership->email,
                        'phone' => $ownership->phone,
                        'active' => $ownership->active,
                        'default' => $ownership->pivot->default ?? false,
                        'settings' => $this->when($ownership->relationLoaded('settings'), function () use ($ownership) {
                            return $ownership->settings->map(function ($setting) {
                                return [
                                    'key' => $setting->key,
                                    'value' => $setting->value,
                                    'value_type' => $setting->value_type,
                                    'group' => $setting->group,
                                    'description' => $setting->description,
                                ];
                            });
                        }),
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

