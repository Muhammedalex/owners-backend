<?php

namespace App\Services\V1\Ownership;

use App\Models\V1\Ownership\Building;
use App\Models\V1\Ownership\Portfolio;
use App\Repositories\V1\Ownership\Interfaces\BuildingRepositoryInterface;
use App\Services\V1\Document\DocumentService;
use App\Services\V1\Media\MediaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BuildingService
{
    public function __construct(
        private BuildingRepositoryInterface $buildingRepository,
        private MediaService $mediaService,
        private DocumentService $documentService
    ) {}

    /**
     * Get all buildings with pagination.
     * Ownership scope is mandatory.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->buildingRepository->paginate($perPage, $filters);
    }

    /**
     * Get all buildings.
     * Ownership scope is mandatory.
     */
    public function all(array $filters = []): Collection
    {
        return $this->buildingRepository->all($filters);
    }

    /**
     * Find building by ID.
     */
    public function find(int $id): ?Building
    {
        return $this->buildingRepository->find($id);
    }

    /**
     * Find building by UUID.
     */
    public function findByUuid(string $uuid): ?Building
    {
        return $this->buildingRepository->findByUuid($uuid);
    }

    /**
     * Find building by code.
     */
    public function findByCode(string $code): ?Building
    {
        return $this->buildingRepository->findByCode($code);
    }

    /**
     * Create a new building.
     * Ownership ID is mandatory and must be provided in data.
     */
    public function create(array $data): Building
    {
        return DB::transaction(function () use ($data) {
            if (!isset($data['ownership_id'])) {
                throw new \InvalidArgumentException('Ownership ID is required to create a building.');
            }
            return $this->buildingRepository->create($data);
        });
    }

    /**
     * Update building.
     */
    public function update(Building $building, array $data): Building
    {
        return DB::transaction(function () use ($building, $data) {
            // Verify portfolio belongs to same ownership if portfolio_id is being updated
            if (isset($data['portfolio_id'])) {
                $portfolio = Portfolio::where('id', $data['portfolio_id'])
                    ->where('ownership_id', $building->ownership_id)
                    ->firstOrFail();
            }
            
            return $this->buildingRepository->update($building, $data);
        });
    }

    /**
     * Delete building.
     */
    public function delete(Building $building): bool
    {
        return DB::transaction(function () use ($building) {
            // Load relationships
            $building->load(['mediaFiles', 'documents']);

            // Delete all media files
            foreach ($building->mediaFiles as $mediaFile) {
                $this->mediaService->delete($mediaFile);
            }

            // Delete all documents
            foreach ($building->documents as $document) {
                $this->documentService->delete($document);
            }

            return $this->buildingRepository->delete($building);
        });
    }

    /**
     * Activate building.
     */
    public function activate(Building $building): Building
    {
        return $this->buildingRepository->activate($building);
    }

    /**
     * Deactivate building.
     */
    public function deactivate(Building $building): Building
    {
        return $this->buildingRepository->deactivate($building);
    }
}

