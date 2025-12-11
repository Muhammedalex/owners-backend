<?php

namespace App\Services\V1\Contract;

use App\Models\V1\Contract\ContractTerm;
use App\Repositories\V1\Contract\Interfaces\ContractTermRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ContractTermService
{
    public function __construct(
        private ContractTermRepositoryInterface $contractTermRepository
    ) {}

    /**
     * Get all terms for a contract.
     */
    public function getByContract(int $contractId): Collection
    {
        return $this->contractTermRepository->getByContract($contractId);
    }

    /**
     * Find term by ID.
     */
    public function find(int $id): ?ContractTerm
    {
        return $this->contractTermRepository->find($id);
    }

    /**
     * Find term by contract and key.
     */
    public function findByContractAndKey(int $contractId, string $key): ?ContractTerm
    {
        return $this->contractTermRepository->findByContractAndKey($contractId, $key);
    }

    /**
     * Create a new term.
     */
    public function create(array $data): ContractTerm
    {
        return DB::transaction(function () use ($data) {
            return $this->contractTermRepository->create($data);
        });
    }

    /**
     * Update term.
     */
    public function update(ContractTerm $term, array $data): ContractTerm
    {
        return DB::transaction(function () use ($term, $data) {
            return $this->contractTermRepository->update($term, $data);
        });
    }

    /**
     * Delete term.
     */
    public function delete(ContractTerm $term): bool
    {
        return DB::transaction(function () use ($term) {
            return $this->contractTermRepository->delete($term);
        });
    }

    /**
     * Delete all terms for a contract.
     */
    public function deleteByContract(int $contractId): bool
    {
        return DB::transaction(function () use ($contractId) {
            return $this->contractTermRepository->deleteByContract($contractId);
        });
    }
}

