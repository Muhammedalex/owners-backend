<?php

namespace App\Repositories\V1\Contract\Interfaces;

use App\Models\V1\Contract\ContractTerm;
use Illuminate\Database\Eloquent\Collection;

interface ContractTermRepositoryInterface
{
    /**
     * Get all terms for a contract.
     */
    public function getByContract(int $contractId): Collection;

    /**
     * Find term by ID.
     */
    public function find(int $id): ?ContractTerm;

    /**
     * Find term by contract and key.
     */
    public function findByContractAndKey(int $contractId, string $key): ?ContractTerm;

    /**
     * Create a new term.
     */
    public function create(array $data): ContractTerm;

    /**
     * Update term.
     */
    public function update(ContractTerm $term, array $data): ContractTerm;

    /**
     * Delete term.
     */
    public function delete(ContractTerm $term): bool;

    /**
     * Delete all terms for a contract.
     */
    public function deleteByContract(int $contractId): bool;
}

