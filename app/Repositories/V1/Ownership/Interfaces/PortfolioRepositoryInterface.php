<?php

namespace App\Repositories\V1\Ownership\Interfaces;

use App\Models\V1\Ownership\Portfolio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PortfolioRepositoryInterface
{
    /**
     * Get all portfolios with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all portfolios.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find portfolio by ID.
     */
    public function find(int $id): ?Portfolio;

    /**
     * Find portfolio by UUID.
     */
    public function findByUuid(string $uuid): ?Portfolio;

    /**
     * Find portfolio by code.
     */
    public function findByCode(string $code): ?Portfolio;

    /**
     * Create a new portfolio.
     */
    public function create(array $data): Portfolio;

    /**
     * Update portfolio.
     */
    public function update(Portfolio $portfolio, array $data): Portfolio;

    /**
     * Delete portfolio.
     */
    public function delete(Portfolio $portfolio): bool;

    /**
     * Activate portfolio.
     */
    public function activate(Portfolio $portfolio): Portfolio;

    /**
     * Deactivate portfolio.
     */
    public function deactivate(Portfolio $portfolio): Portfolio;
}

