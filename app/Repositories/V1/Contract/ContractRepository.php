<?php

namespace App\Repositories\V1\Contract;

use App\Models\V1\Contract\Contract;
use App\Repositories\V1\Contract\Interfaces\ContractRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ContractRepository implements ContractRepositoryInterface
{
    /**
     * Get all contracts with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Contract::query();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('ejar_code', 'like', "%{$search}%")
                    ->orWhereHas('tenant.user', function ($userQuery) use ($search) {
                        $userQuery->where('first', 'like', "%{$search}%")
                            ->orWhere('last', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('units', function ($unitQuery) use ($search) {
                        $unitQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('number', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        }

        if (isset($filters['unit_id'])) {
            $query->forUnit($filters['unit_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->inDateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['ejar_code'])) {
            $query->where('ejar_code', $filters['ejar_code']);
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'documents'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all contracts.
     */
    public function all(array $filters = []): Collection
    {
        $query = Contract::query();

        // Apply filters (same as paginate)
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('ejar_code', 'like', "%{$search}%")
                    ->orWhereHas('tenant.user', function ($userQuery) use ($search) {
                        $userQuery->where('first', 'like', "%{$search}%")
                            ->orWhere('last', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('units', function ($unitQuery) use ($search) {
                        $unitQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('number', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        }

        if (isset($filters['unit_id'])) {
            $query->forUnit($filters['unit_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->inDateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['ejar_code'])) {
            $query->where('ejar_code', $filters['ejar_code']);
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'documents'])
            ->latest()
            ->get();
    }

    /**
     * Find contract by ID.
     */
    public function find(int $id): ?Contract
    {
        return Contract::with(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents'])
            ->find($id);
    }

    /**
     * Find contract by UUID.
     */
    public function findByUuid(string $uuid): ?Contract
    {
        return Contract::where('uuid', $uuid)
            ->with(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents'])
            ->first();
    }

    /**
     * Find contract by number.
     */
    public function findByNumber(string $number): ?Contract
    {
        return Contract::where('number', $number)
            ->with(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents'])
            ->first();
    }

    /**
     * Find active contract for unit.
     */
    public function findActiveContractForUnit(int $unitId): ?Contract
    {
        // Use units pivot table only
        return Contract::where('status', 'active')
            ->where('start', '<=', now())
            ->where('end', '>=', now())
            ->whereHas('units', function ($uq) use ($unitId) {
                $uq->where('units.id', $unitId);
            })
            ->with(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'documents'])
            ->first();
    }

    /**
     * Create a new contract.
     */
    public function create(array $data): Contract
    {
        // Ensure UUID is set if not provided (trait should handle this, but as fallback)
        if (!isset($data['uuid']) || empty($data['uuid'])) {
            $data['uuid'] = (string) \Illuminate\Support\Str::uuid();
        }

        return Contract::create($data);
    }

    /**
     * Update contract.
     */
    public function update(Contract $contract, array $data): Contract
    {
        $contract->update($data);
        return $contract->fresh(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents']);
    }

    /**
     * Delete contract.
     */
    public function delete(Contract $contract): bool
    {
        return $contract->delete();
    }

    /**
     * Approve contract.
     */
    public function approve(Contract $contract, int $approvedBy): Contract
    {
        $contract->update([
            'status' => 'active',
            'approved_by' => $approvedBy,
        ]);
        return $contract->fresh(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents']);
    }
}

