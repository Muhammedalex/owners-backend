<?php

namespace App\Repositories\V1\Contract;

use App\Models\V1\Contract\ContractTerm;
use App\Repositories\V1\Contract\Interfaces\ContractTermRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ContractTermRepository implements ContractTermRepositoryInterface
{
    /**
     * Get all terms for a contract.
     */
    public function getByContract(int $contractId): Collection
    {
        return ContractTerm::where('contract_id', $contractId)
            ->get();
    }

    /**
     * Find term by ID.
     */
    public function find(int $id): ?ContractTerm
    {
        return ContractTerm::with('contract')->find($id);
    }

    /**
     * Find term by contract and key.
     */
    public function findByContractAndKey(int $contractId, string $key): ?ContractTerm
    {
        return ContractTerm::where('contract_id', $contractId)
            ->where('key', $key)
            ->first();
    }

    /**
     * Create a new term.
     */
    public function create(array $data): ContractTerm
    {
        return ContractTerm::create($data);
    }

    /**
     * Update term.
     */
    public function update(ContractTerm $term, array $data): ContractTerm
    {
        $term->update($data);
        return $term->fresh('contract');
    }

    /**
     * Delete term.
     */
    public function delete(ContractTerm $term): bool
    {
        return $term->delete();
    }

    /**
     * Delete all terms for a contract.
     */
    public function deleteByContract(int $contractId): bool
    {
        return ContractTerm::where('contract_id', $contractId)->delete();
    }
}

