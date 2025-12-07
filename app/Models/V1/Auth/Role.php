<?php

namespace App\Models\V1\Auth;

use App\Models\Scopes\ExcludeSystemRolesScope;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new ExcludeSystemRolesScope());
    }

    /**
     * Get roles without the global scope (including system roles).
     * Use this only when you explicitly need to access system roles (e.g., in seeders).
     */
    public static function withSystemRoles(): Builder
    {
        return static::withoutGlobalScope(ExcludeSystemRolesScope::class);
    }
}

