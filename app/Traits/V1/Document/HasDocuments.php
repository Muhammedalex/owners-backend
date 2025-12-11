<?php

namespace App\Traits\V1\Document;

use App\Models\V1\Document\Document;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDocuments
{
    /**
     * Get all documents for this model.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'entity');
    }

    /**
     * Get documents by type.
     */
    public function documentsOfType(string $type): MorphMany
    {
        return $this->documents()->where('type', $type);
    }

    /**
     * Get public documents.
     */
    public function publicDocuments(): MorphMany
    {
        return $this->documents()->where('public', true);
    }

    /**
     * Get private documents.
     */
    public function privateDocuments(): MorphMany
    {
        return $this->documents()->where('public', false);
    }

    /**
     * Get non-expired documents.
     */
    public function validDocuments(): MorphMany
    {
        return $this->documents()->notExpired();
    }

    /**
     * Get expired documents.
     */
    public function expiredDocuments(): MorphMany
    {
        return $this->documents()->expired();
    }

    /**
     * Get documents that expire soon.
     */
    public function expiringSoonDocuments(): MorphMany
    {
        return $this->documents()->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30));
    }

    /**
     * Get first document by type.
     */
    public function getDocument(string $type): ?Document
    {
        return $this->documentsOfType($type)->first();
    }

    /**
     * Get all documents by type.
     */
    public function getDocuments(string $type)
    {
        return $this->documentsOfType($type)->get();
    }

    /**
     * Check if model has documents of a specific type.
     */
    public function hasDocuments(string $type): bool
    {
        return $this->documentsOfType($type)->exists();
    }

    /**
     * Check if model has any documents.
     */
    public function hasAnyDocuments(): bool
    {
        return $this->documents()->exists();
    }
}

