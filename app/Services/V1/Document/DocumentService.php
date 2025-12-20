<?php

namespace App\Services\V1\Document;

use App\Models\V1\Document\Document;
use App\Repositories\V1\Document\Interfaces\DocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository
    ) {}

    /**
     * Upload a document for an entity.
     */
    public function upload(
        Model $entity,
        UploadedFile $file,
        string $type,
        int $ownershipId,
        string $title,
        ?int $uploadedBy = null,
        ?string $description = null,
        bool $public = false,
        ?\DateTimeInterface $expiresAt = null
    ): Document {
        return DB::transaction(function () use ($entity, $file, $type, $ownershipId, $title, $uploadedBy, $description, $public, $expiresAt) {
            // Validate file
            $this->validateFile($file, $type);

            // Generate storage path
            $path = $this->generatePath($entity, $type);
            $fileName = $this->generateFileName($file, $type);
            $fullPath = $path . '/' . $fileName;

            // Store file
            $storedPath = $file->storeAs($path, $fileName, 'public');

            // Get file info
            $fileInfo = [
                'ownership_id' => $ownershipId,
                'type' => $type,
                'title' => $title,
                'description' => $description,
                'path' => $storedPath,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'uploaded_by' => $uploadedBy,
                'public' => $public,
                'expires_at' => $expiresAt,
            ];

            // Create document record
            return $this->documentRepository->create($fileInfo);
        });
    }

    /**
     * Delete a document.
     */
    public function delete(Document $document): bool
    {
        return DB::transaction(function () use ($document) {
            // Delete file from storage
            if (Storage::disk('public')->exists($document->path)) {
                Storage::disk('public')->delete($document->path);
            }

            // Delete record
            return $this->documentRepository->delete($document);
        });
    }

    /**
     * Download a document.
     */
    public function download(Document $document): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (!Storage::disk('public')->exists($document->path)) {
            abort(404, 'Document file not found');
        }

        $fileName = $document->title . '.' . pathinfo($document->path, PATHINFO_EXTENSION);
        $filePath = Storage::disk('public')->path($document->path);
        
        // Get MIME type from database or detect from file
        $mimeType = $document->mime;
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
     * Update document metadata.
     */
    public function update(Document $document, array $data): Document
    {
        return DB::transaction(function () use ($document, $data) {
            return $this->documentRepository->update($document, $data);
        });
    }

    /**
     * Archive expired documents.
     */
    public function archiveExpired(): int
    {
        $expiredDocuments = $this->documentRepository->getExpired();
        $archived = 0;

        foreach ($expiredDocuments as $document) {
            // Move to archive folder
            $archivePath = 'archive/' . $document->path;
            if (Storage::disk('public')->exists($document->path)) {
                Storage::disk('public')->move($document->path, $archivePath);
                $document->update(['path' => $archivePath]);
                $archived++;
            }
        }

        return $archived;
    }

    /**
     * Validate file.
     */
    private function validateFile(UploadedFile $file, string $type): void
    {
        // Get settings for documents
        $maxSize = config('documents.max_size_mb', 10) * 1024 * 1024; // Convert MB to bytes
        $allowedTypes = config("documents.allowed_types.{$type}", config('documents.allowed_types.default', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png']));

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
        
        return "documents/{$entityType}/{$entity->id}/{$type}";
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
}

