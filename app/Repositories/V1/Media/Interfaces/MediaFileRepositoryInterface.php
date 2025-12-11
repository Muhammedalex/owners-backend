<?php

namespace App\Repositories\V1\Media\Interfaces;

use App\Models\V1\Media\MediaFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface MediaFileRepositoryInterface
{
    /**
     * Get all media files for an entity.
     */
    public function getForEntity(Model $entity, ?string $type = null): Collection;

    /**
     * Find media file by ID.
     */
    public function find(int $id): ?MediaFile;

    /**
     * Create a new media file.
     */
    public function create(array $data): MediaFile;

    /**
     * Update media file.
     */
    public function update(MediaFile $mediaFile, array $data): MediaFile;

    /**
     * Delete media file.
     */
    public function delete(MediaFile $mediaFile): bool;

    /**
     * Get max order for entity and type.
     */
    public function getMaxOrder(Model $entity, string $type): int;

    /**
     * Update media file order.
     */
    public function updateOrder(int $mediaFileId, int $order): bool;
}

