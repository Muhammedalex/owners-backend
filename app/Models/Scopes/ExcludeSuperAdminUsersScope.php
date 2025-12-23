<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class ExcludeSuperAdminUsersScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Excludes Super Admin users from queries for non-Super Admin users.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // If current user is Super Admin, don't apply the scope (they can see all users)
        $user = Auth::user();
        if ($user && $user->isSuperAdmin()) {
            return;
        }

        // For non-Super Admin users, exclude users who have the Super Admin role
        $builder->whereDoesntHave('roles', function ($query) {
            $query->where('name', 'Super Admin');
        });
    }
}

