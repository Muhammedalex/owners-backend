<?php

namespace App\Services\V1\Media;

use App\Models\V1\Media\MediaFile;
use App\Repositories\V1\Media\Interfaces\MediaFileRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class MediaService
{
    public function __construct(
        private MediaFileRepositoryInterface $mediaRepository
    ) {}

    /**
     * Upload a media file for an entity.
     */
    public function upload(
        Model $entity,
        UploadedFile $file,
        string $type,
        int $ownershipId,
        ?int $uploadedBy = null,
        ?string $title = null,
        ?string $description = null,
        bool $public = true,
        ?int $order = null
    ): MediaFile {
        return DB::transaction(function () use ($entity, $file, $type, $ownershipId, $uploadedBy, $title, $description, $public, $order) {
            // Validate file
            $this->validateFile($file, $type);

            // Generate storage path
            $path = $this->generatePath($entity, $type);
            $fileName = $this->generateFileName($file, $type);
            $fullPath = $path . '/' . $fileName;

            // Store file
            $storedPath = $file->storeAs($path, $fileName, 'public');

            // Process image if needed
            if ($this->isImage($file)) {
                $this->processImage($storedPath, $type);
            }

            // Get file info
            $fileInfo = [
                'ownership_id' => $ownershipId,
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'type' => $type,
                'path' => $storedPath,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'title' => $title ?? $file->getClientOriginalName(),
                'description' => $description,
                'uploaded_by' => $uploadedBy,
                'public' => $public,
            ];

            // Set order
            if ($order === null) {
                $maxOrder = $this->mediaRepository->getMaxOrder($entity, $type);
                $fileInfo['order'] = $maxOrder + 1;
            } else {
                $fileInfo['order'] = $order;
            }

            // Create media file record
            return $this->mediaRepository->create($fileInfo);
        });
    }

    /**
     * Delete a media file.
     */
    public function delete(MediaFile $mediaFile): bool
    {
        return DB::transaction(function () use ($mediaFile) {
            // Delete file from storage
            if (Storage::disk('public')->exists($mediaFile->path)) {
                Storage::disk('public')->delete($mediaFile->path);
                
                // Delete thumbnails if image
                if ($mediaFile->isImage()) {
                    $this->deleteThumbnails($mediaFile->path);
                }
            }

            // Delete record
            return $this->mediaRepository->delete($mediaFile);
        });
    }

    /**
     * Download a media file.
     */
    public function download(MediaFile $mediaFile): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (!Storage::disk('public')->exists($mediaFile->path)) {
            abort(404, 'Media file not found');
        }

        $fileName = $mediaFile->name ?? ($mediaFile->title ?? 'media_file') . '.' . pathinfo($mediaFile->path, PATHINFO_EXTENSION);
        $filePath = Storage::disk('public')->path($mediaFile->path);
        
        // Get MIME type from database or detect from file
        $mimeType = $mediaFile->mime;
        if (!$mimeType && file_exists($filePath)) {
            $mimeType = mime_content_type($filePath) ?? 'application/octet-stream';
        }
        $mimeType = $mimeType ?? 'application/octet-stream';
 
        return response()->download(
            $filePath,
            $fileName,
            [
                'Content-Type' => $mimeType,
            ]
        );
    }

    /**
     * Reorder media files.
     */
    public function reorder(array $mediaFileIds): bool
    {
        return DB::transaction(function () use ($mediaFileIds) {
            foreach ($mediaFileIds as $order => $mediaFileId) {
                $this->mediaRepository->updateOrder($mediaFileId, $order + 1);
            }
            return true;
        });
    }

    /**
     * Update media file metadata.
     */
    public function update(MediaFile $mediaFile, array $data): MediaFile
    {
        return DB::transaction(function () use ($mediaFile, $data) {
            return $this->mediaRepository->update($mediaFile, $data);
        });
    }

    /**
     * Validate file.
     */
    private function validateFile(UploadedFile $file, string $type): void
    {
        // Get settings for media
        $maxSize = config('media.max_size_mb', 5) * 1024 * 1024; // Convert MB to bytes
        $allowedTypes = config("media.allowed_types.{$type}", config('media.allowed_types.default', ['jpg', 'jpeg', 'png', 'gif']));

        // Check file size
        if ($file->getSize() > $maxSize) {
            throw new \Exception("File size exceeds maximum allowed size of " . ($maxSize / 1024 / 1024) . "MB");
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            throw new \Exception("File type '{$extension}' is not allowed. Allowed types: " . implode(', ', $allowedTypes));
        }
    }

    /**
     * Generate storage path.
     */
    private function generatePath(Model $entity, string $type): string
    {
        $entityType = str_replace('App\\Models\\V1\\', '', get_class($entity));
        $entityType = str_replace('\\', '/', $entityType);
        $entityType = strtolower($entityType);
        
        return "media/{$entityType}/{$entity->id}/{$type}";
    }

    /**
     * Generate file name.
     */
    private function generateFileName(UploadedFile $file, string $type): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = str()->random(8);
        
        return "{$type}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Check if file is an image.
     */
    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Process image (resize, optimize).
     */
    private function processImage(string $path, string $type): void
    {
        $autoResize = config('media.auto_resize_images', true);
        if (!$autoResize) {
            return;
        }

        $fullPath = Storage::disk('public')->path($path);
        
        try {
            $image = Image::read($fullPath);
            
            // Get max dimensions from config
            $maxWidth = config("media.max_width.{$type}", config('media.max_width.default', 1920));
            $maxHeight = config("media.max_height.{$type}", config('media.max_height.default', 1080));
            
            // Resize if needed
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->scaleDown($maxWidth, $maxHeight);
            }

            // Optimize quality
            $quality = config('media.image_quality', 85);
            $image->save($fullPath, $quality);

            // Generate thumbnails if needed
            $this->generateThumbnails($path, $type);
        } catch (\Exception $e) {
            // Log error but don't fail upload
            Log::warning("Failed to process image: {$e->getMessage()}");
        }
    }

    /**
     * Generate thumbnails for image.
     */
    private function generateThumbnails(string $path, string $type): void
    {
        $generateThumbnails = config('media.generate_thumbnails', true);
        if (!$generateThumbnails) {
            return;
        }

        $fullPath = Storage::disk('public')->path($path);
        $pathInfo = pathinfo($path);
        $thumbnails = config("media.thumbnails.{$type}", config('media.thumbnails.default', [
            'small' => [200, 200],
            'medium' => [400, 400],
            'large' => [800, 800],
        ]));

        try {
            foreach ($thumbnails as $sizeName => $dimensions) {
                [$width, $height] = $dimensions;
                $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$sizeName}." . $pathInfo['extension'];
                $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);
                
                // Read image and resize for thumbnail
                $thumbnail = Image::read($fullPath);
                $thumbnail->cover($width, $height);
                $thumbnail->save($thumbnailFullPath, config('media.image_quality', 85));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to generate thumbnails: {$e->getMessage()}");
        }
    }

    /**
     * Delete thumbnails.
     */
    private function deleteThumbnails(string $path): void
    {
        $pathInfo = pathinfo($path);
        $thumbnails = config('media.thumbnails.default', [
            'small' => [200, 200],
            'medium' => [400, 400],
            'large' => [800, 800],
        ]);

        foreach (array_keys($thumbnails) as $sizeName) {
            $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$sizeName}." . $pathInfo['extension'];
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }
    }
}

