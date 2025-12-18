<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class ExcludeSystemRolesScope implements Scope
{
    /**
     * System roles that should never be visible in queries.
     * These are system-level roles that should be hidden from all API responses.
     * Exception: Super Admin can see all roles including system roles.
     */
    protected array $systemRoles = [
        // 'super admin'
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // If current user is Super Admin, don't apply the scope
        $user = Auth::user();
        if ($user && $user->isSuperAdmin()) {
            return;
        }

        // For non-Super Admin users, exclude system roles
        $builder->whereNotIn('name', $this->systemRoles);
    }
}
