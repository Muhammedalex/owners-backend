<?php

namespace App\Repositories\V1\Contract\Interfaces;

use App\Models\V1\Contract\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ContractRepositoryInterface
{
    /**
     * Get all contracts with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all contracts.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find contract by ID.
     */
    public function find(int $id): ?Contract;

    /**
     * Find contract by UUID.
     */
    public function findByUuid(string $uuid): ?Contract;

    /**
     * Find contract by number.
     */
    public function findByNumber(string $number): ?Contract;

    /**
     * Find active contract for unit.
     */
    public function findActiveContractForUnit(int $unitId): ?Contract;

    /**
     * Create a new contract.
     */
    public function create(array $data): Contract;

    /**
     * Update contract.
     */
    public function update(Contract $contract, array $data): Contract;

    /**
     * Delete contract.
     */
    public function delete(Contract $contract): bool;

    /**
     * Approve contract.
     */
    public function approve(Contract $contract, int $approvedBy): Contract;
}

