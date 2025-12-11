<?php

namespace App\Traits\V1\Media;

use App\Models\V1\Media\MediaFile;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMedia
{
    /**
     * Get all media files for this model.
     */
    public function mediaFiles(): MorphMany
    {
        return $this->morphMany(MediaFile::class, 'entity');
    }

    /**
     * Get media files by type.
     */
    public function mediaFilesOfType(string $type): MorphMany
    {
        return $this->mediaFiles()->where('type', $type);
    }

    /**
     * Get public media files.
     */
    public function publicMediaFiles(): MorphMany
    {
        return $this->mediaFiles()->where('public', true);
    }

    /**
     * Get private media files.
     */
    public function privateMediaFiles(): MorphMany
    {
        return $this->mediaFiles()->where('public', false);
    }

    /**
     * Get ordered media files.
     */
    public function orderedMediaFiles(): MorphMany
    {
        return $this->mediaFiles()->orderBy('order')->orderBy('created_at');
    }

    /**
     * Get first media file by type.
     */
    public function getMediaFile(string $type): ?MediaFile
    {
        return $this->mediaFilesOfType($type)->first();
    }

    /**
     * Get all media files by type.
     */
    public function getMediaFiles(string $type)
    {
        return $this->mediaFilesOfType($type)->ordered()->get();
    }

    /**
     * Check if model has media files of a specific type.
     */
    public function hasMediaFiles(string $type): bool
    {
        return $this->mediaFilesOfType($type)->exists();
    }

    /**
     * Get the main/cover media file (first ordered).
     */
    public function getMainMediaFile(): ?MediaFile
    {
        return $this->mediaFiles()->ordered()->first();
    }
}

