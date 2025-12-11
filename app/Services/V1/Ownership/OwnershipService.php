<?php

namespace App\Services\V1\Ownership;

use App\Models\V1\Ownership\Building;
use App\Models\V1\Ownership\Ownership;
use App\Models\V1\Ownership\Portfolio;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use Database\Seeders\V1\Setting\SystemSettingSeeder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OwnershipService
{
    public function __construct(
        private OwnershipRepositoryInterface $ownershipRepository
    ) {}

    /**
     * Get all ownerships with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->ownershipRepository->paginate($perPage, $filters);
    }

    /**
     * Get all ownerships.
     */
    public function all(array $filters = []): Collection
    {
        return $this->ownershipRepository->all($filters);
    }

    /**
     * Find ownership by ID.
     */
    public function find(int $id): ?Ownership
    {
        return $this->ownershipRepository->find($id);
    }

    /**
     * Find ownership by UUID.
     */
    public function findByUuid(string $uuid): ?Ownership
    {
        return $this->ownershipRepository->findByUuid($uuid);
    }

    /**
     * Create a new ownership.
     * Automatically creates default portfolio and building, and seeds default settings.
     */
    public function create(array $data): Ownership
    {
        return DB::transaction(function () use ($data) {
            // Create ownership
            $ownership = $this->ownershipRepository->create($data);

            // Create default portfolio
            $portfolio = $this->createDefaultPortfolio($ownership);

            // Create default building
            $this->createDefaultBuilding($ownership, $portfolio);

            // Seed default settings for this ownership
            $this->seedDefaultSettings($ownership);

            return $ownership;
        });
    }

    /**
     * Create default portfolio for ownership.
     */
    private function createDefaultPortfolio(Ownership $ownership): Portfolio
    {
        $portfolioCount = Portfolio::where('ownership_id', $ownership->id)->count();
        $index = $portfolioCount + 1;

        return Portfolio::create([
            'uuid' => (string) Str::uuid(),
            'ownership_id' => $ownership->id,
            'parent_id' => null,
            'name' => 'المحفظة الرئيسية - ' . $ownership->name,
            'code' => $this->generatePortfolioCode($ownership->id, $index),
            'type' => 'general',
            'description' => 'المحفظة الافتراضية للملكية ' . $ownership->name,
            'area' => null,
            'active' => true,
        ]);
    }

    /**
     * Create default building for ownership.
     */
    private function createDefaultBuilding(Ownership $ownership, Portfolio $portfolio): Building
    {
        $buildingCount = Building::where('portfolio_id', $portfolio->id)->count();
        $index = $buildingCount + 1;

        return Building::create([
            'uuid' => (string) Str::uuid(),
            'portfolio_id' => $portfolio->id,
            'ownership_id' => $ownership->id,
            'parent_id' => null,
            'name' => 'المبنى الرئيسي',
            'code' => $this->generateBuildingCode($portfolio->id, $index),
            'type' => 'mixed',
            'description' => 'المبنى الافتراضي للملكية ' . $ownership->name,
            'street' => $ownership->street,
            'city' => $ownership->city,
            'state' => $ownership->state,
            'country' => $ownership->country ?? 'Saudi Arabia',
            'zip_code' => $ownership->zip_code,
            'latitude' => null,
            'longitude' => null,
            'floors' => 1,
            'year' => null,
            'active' => true,
        ]);
    }

    /**
     * Generate unique portfolio code.
     */
    private function generatePortfolioCode(int $ownershipId, int $index): string
    {
        return 'PORT-' . str_pad($ownershipId, 3, '0', STR_PAD_LEFT) . '-' . str_pad($index, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique building code.
     */
    private function generateBuildingCode(int $portfolioId, int $index): string
    {
        return 'BLD-' . str_pad($portfolioId, 3, '0', STR_PAD_LEFT) . '-' . str_pad($index, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Seed default settings for ownership.
     */
    private function seedDefaultSettings(Ownership $ownership): void
    {
        $seeder = new SystemSettingSeeder();
        $seeder->seedForOwnership($ownership);
    }

    /**
     * Update ownership.
     */
    public function update(Ownership $ownership, array $data): Ownership
    {
        return DB::transaction(function () use ($ownership, $data) {
            return $this->ownershipRepository->update($ownership, $data);
        });
    }

    /**
     * Delete ownership.
     */
    public function delete(Ownership $ownership): bool
    {
        return DB::transaction(function () use ($ownership) {
            return $this->ownershipRepository->delete($ownership);
        });
    }

    /**
     * Activate ownership.
     */
    public function activate(Ownership $ownership): Ownership
    {
        return $this->ownershipRepository->activate($ownership);
    }

    /**
     * Deactivate ownership.
     */
    public function deactivate(Ownership $ownership): Ownership
    {
        return $this->ownershipRepository->deactivate($ownership);
    }
}

