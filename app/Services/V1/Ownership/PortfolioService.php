<?php

namespace App\Services\V1\Ownership;

use App\Models\V1\Ownership\Portfolio;
use App\Repositories\V1\Ownership\Interfaces\PortfolioRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    public function __construct(
        private PortfolioRepositoryInterface $portfolioRepository
    ) {}

    /**
     * Get all portfolios with pagination.
     * Ownership scope is mandatory.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->portfolioRepository->paginate($perPage, $filters);
    }

    /**
     * Get all portfolios.
     * Ownership scope is mandatory.
     */
    public function all(array $filters = []): Collection
    {
        return $this->portfolioRepository->all($filters);
    }

    /**
     * Find portfolio by ID.
     */
    public function find(int $id): ?Portfolio
    {
        return $this->portfolioRepository->find($id);
    }

    /**
     * Find portfolio by UUID.
     */
    public function findByUuid(string $uuid): ?Portfolio
    {
        return $this->portfolioRepository->findByUuid($uuid);
    }

    /**
     * Find portfolio by code.
     */
    public function findByCode(string $code): ?Portfolio
    {
        return $this->portfolioRepository->findByCode($code);
    }

    /**
     * Create a new portfolio.
     * Ownership ID is mandatory and must be provided in data.
     */
    public function create(array $data): Portfolio
    {
        return DB::transaction(function () use ($data) {
            if (!isset($data['ownership_id'])) {
                throw new \InvalidArgumentException('Ownership ID is required to create a portfolio.');
            }
            return $this->portfolioRepository->create($data);
        });
    }

    /**
     * Update portfolio.
     */
    public function update(Portfolio $portfolio, array $data): Portfolio
    {
        return DB::transaction(function () use ($portfolio, $data) {
            return $this->portfolioRepository->update($portfolio, $data);
        });
    }

    /**
     * Delete portfolio.
     */
    public function delete(Portfolio $portfolio): bool
    {
        return DB::transaction(function () use ($portfolio) {
            return $this->portfolioRepository->delete($portfolio);
        });
    }

    /**
     * Activate portfolio.
     */
    public function activate(Portfolio $portfolio): Portfolio
    {
        return $this->portfolioRepository->activate($portfolio);
    }

    /**
     * Deactivate portfolio.
     */
    public function deactivate(Portfolio $portfolio): Portfolio
    {
        return $this->portfolioRepository->deactivate($portfolio);
    }
}

