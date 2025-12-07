<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ExcludeSystemRolesScope implements Scope
{
    /**
     * System roles that should never be visible in queries.
     * These are system-level roles that should be hidden from all API responses.
     */
    protected array $systemRoles = [
        
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotIn('name', $this->systemRoles);
    }
}
