<?php

namespace App\Repositories\V1\Ownership;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OwnershipRepository implements OwnershipRepositoryInterface
{
    /**
     * Get eager loading relationships with Super Admin user filtering.
     * 
     * @return array
     */
    private function getEagerLoadRelations(): array
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser instanceof User && $currentUser->isSuperAdmin();

        $eagerLoad = ['createdBy', 'boardMembers.user'];
        
        if ($isSuperAdmin) {
            // Super Admin can see all users
            $eagerLoad[] = 'userMappings.user';
        } else {
            // Non-Super Admin: filter out Super Admin users from userMappings
            $eagerLoad['userMappings.user'] = function ($query) {
                $query->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('model_has_roles')
                        ->whereColumn('model_has_roles.model_id', 'users.id')
                        ->where('model_has_roles.model_type', User::class)
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->where('roles.name', 'Super Admin');
                });
            };
        }

        return $eagerLoad;
    }

    /**
     * Get all ownerships with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Ownership::query();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('legal', 'like', "%{$search}%")
                    ->orWhere('registration', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['ownership_type'])) {
            $query->ofOwnershipType($filters['ownership_type']);
        }

        if (isset($filters['city'])) {
            $query->inCity($filters['city']);
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('id', $filters['ownership_ids']);
        }

        return $query->with($this->getEagerLoadRelations())
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all ownerships.
     */
    public function all(array $filters = []): Collection
    {
        $query = Ownership::query();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('legal', 'like', "%{$search}%")
                    ->orWhere('registration', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['ownership_type'])) {
            $query->ofOwnershipType($filters['ownership_type']);
        }

        if (isset($filters['city'])) {
            $query->inCity($filters['city']);
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('id', $filters['ownership_ids']);
        }

        return $query->with($this->getEagerLoadRelations())
            ->latest()
            ->get();
    }

    /**
     * Find ownership by ID.
     */
    public function find(int $id): ?Ownership
    {
        return Ownership::with($this->getEagerLoadRelations())
            ->find($id);
    }

    /**
     * Find ownership by UUID.
     */
    public function findByUuid(string $uuid): ?Ownership
    {
        return Ownership::where('uuid', $uuid)
            ->with($this->getEagerLoadRelations())
            ->first();
    }

    /**
     * Create a new ownership.
     */
    public function create(array $data): Ownership
    {
        return Ownership::create($data);
    }

    /**
     * Update ownership.
     */
    public function update(Ownership $ownership, array $data): Ownership
    {
        $ownership->update($data);
        return $ownership->fresh($this->getEagerLoadRelations());
    }

    /**
     * Delete ownership.
     */
    public function delete(Ownership $ownership): bool
    {
        return $ownership->delete();
    }

    /**
     * Activate ownership.
     */
    public function activate(Ownership $ownership): Ownership
    {
        $ownership->activate();
        return $ownership->fresh($this->getEagerLoadRelations());
    }

    /**
     * Deactivate ownership.
     */
    public function deactivate(Ownership $ownership): Ownership
    {
        $ownership->deactivate();
        return $ownership->fresh($this->getEagerLoadRelations());
    }
}

