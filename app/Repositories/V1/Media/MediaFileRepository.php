<?php

namespace App\Repositories\V1\Media;

use App\Models\V1\Media\MediaFile;
use App\Repositories\V1\Media\Interfaces\MediaFileRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MediaFileRepository implements MediaFileRepositoryInterface
{
    /**
     * Get all media files for an entity.
     */
    public function getForEntity(Model $entity, ?string $type = null): Collection
    {
        $query = MediaFile::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->id);

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->ordered()->get();
    }

    /**
     * Find media file by ID.
     */
    public function find(int $id): ?MediaFile
    {
        return MediaFile::find($id);
    }

    /**
     * Create a new media file.
     */
    public function create(array $data): MediaFile
    {
        return MediaFile::create($data);
    }

    /**
     * Update media file.
     */
    public function update(MediaFile $mediaFile, array $data): MediaFile
    {
        $mediaFile->update($data);
        return $mediaFile->fresh();
    }

    /**
     * Delete media file.
     */
    public function delete(MediaFile $mediaFile): bool
    {
        return $mediaFile->delete();
    }

    /**
     * Get max order for entity and type.
     */
    public function getMaxOrder(Model $entity, string $type): int
    {
        return MediaFile::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->id)
            ->where('type', $type)
            ->max('order') ?? 0;
    }

    /**
     * Update media file order.
     */
    public function updateOrder(int $mediaFileId, int $order): bool
    {
        return MediaFile::where('id', $mediaFileId)->update(['order' => $order]) > 0;
    }
}

