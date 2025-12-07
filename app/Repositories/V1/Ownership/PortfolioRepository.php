<?php

namespace App\Repositories\V1\Ownership;

use App\Models\V1\Ownership\Portfolio;
use App\Repositories\V1\Ownership\Interfaces\PortfolioRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PortfolioRepository implements PortfolioRepositoryInterface
{
    /**
     * Get all portfolios with pagination.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Portfolio::query();

        // Ownership scope is MANDATORY - must be provided
        if (!isset($filters['ownership_id'])) {
            throw new \InvalidArgumentException('Ownership ID is required for portfolio queries.');
        }
        $query->forOwnership($filters['ownership_id']);

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        return $query->with(['ownership', 'parent', 'children', 'locations', 'buildings'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all portfolios.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function all(array $filters = []): Collection
    {
        $query = Portfolio::query();

        // Ownership scope is MANDATORY - must be provided
        if (!isset($filters['ownership_id'])) {
            throw new \InvalidArgumentException('Ownership ID is required for portfolio queries.');
        }
        $query->forOwnership($filters['ownership_id']);

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        return $query->with(['ownership', 'parent', 'children', 'locations', 'buildings'])
            ->latest()
            ->get();
    }

    /**
     * Find portfolio by ID.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function find(int $id): ?Portfolio
    {
        return Portfolio::with(['ownership', 'parent', 'children', 'locations', 'buildings'])
            ->find($id);
    }

    /**
     * Find portfolio by UUID.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function findByUuid(string $uuid): ?Portfolio
    {
        return Portfolio::where('uuid', $uuid)
            ->with(['ownership', 'parent', 'children', 'locations', 'buildings'])
            ->first();
    }

    /**
     * Find portfolio by code.
     * Ownership scope is mandatory - must be provided in filters.
     */
    public function findByCode(string $code): ?Portfolio
    {
        return Portfolio::where('code', $code)
            ->with(['ownership', 'parent', 'children', 'locations', 'buildings'])
            ->first();
    }

    /**
     * Create a new portfolio.
     */
    public function create(array $data): Portfolio
    {
        return Portfolio::create($data);
    }

    /**
     * Update portfolio.
     */
    public function update(Portfolio $portfolio, array $data): Portfolio
    {
        $portfolio->update($data);
        return $portfolio->fresh(['ownership', 'parent', 'children', 'locations', 'buildings']);
    }

    /**
     * Delete portfolio.
     */
    public function delete(Portfolio $portfolio): bool
    {
        return $portfolio->delete();
    }

    /**
     * Activate portfolio.
     */
    public function activate(Portfolio $portfolio): Portfolio
    {
        $portfolio->activate();
        return $portfolio->fresh(['ownership', 'parent', 'children', 'locations', 'buildings']);
    }

    /**
     * Deactivate portfolio.
     */
    public function deactivate(Portfolio $portfolio): Portfolio
    {
        $portfolio->deactivate();
        return $portfolio->fresh(['ownership', 'parent', 'children', 'locations', 'buildings']);
    }
}

