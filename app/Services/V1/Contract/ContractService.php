<?php

namespace App\Services\V1\Contract;

use App\Models\V1\Contract\Contract;
use App\Repositories\V1\Contract\Interfaces\ContractRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(
        private ContractRepositoryInterface $contractRepository
    ) {}

    /**
     * Get all contracts with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->contractRepository->paginate($perPage, $filters);
    }

    /**
     * Get all contracts.
     */
    public function all(array $filters = []): Collection
    {
        return $this->contractRepository->all($filters);
    }

    /**
     * Find contract by ID.
     */
    public function find(int $id): ?Contract
    {
        return $this->contractRepository->find($id);
    }

    /**
     * Find contract by UUID.
     */
    public function findByUuid(string $uuid): ?Contract
    {
        return $this->contractRepository->findByUuid($uuid);
    }

    /**
     * Find contract by number.
     */
    public function findByNumber(string $number): ?Contract
    {
        return $this->contractRepository->findByNumber($number);
    }

    /**
     * Find active contract for unit.
     */
    public function findActiveContractForUnit(int $unitId): ?Contract
    {
        return $this->contractRepository->findActiveContractForUnit($unitId);
    }

    /**
     * Create a new contract.
     */
    public function create(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            return $this->contractRepository->create($data);
        });
    }

    /**
     * Update contract.
     */
    public function update(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data) {
            return $this->contractRepository->update($contract, $data);
        });
    }

    /**
     * Delete contract.
     */
    public function delete(Contract $contract): bool
    {
        return DB::transaction(function () use ($contract) {
            return $this->contractRepository->delete($contract);
        });
    }

    /**
     * Approve contract.
     */
    public function approve(Contract $contract, int $approvedBy): Contract
    {
        return DB::transaction(function () use ($contract, $approvedBy) {
            return $this->contractRepository->approve($contract, $approvedBy);
        });
    }
}

