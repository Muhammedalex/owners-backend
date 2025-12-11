<?php

namespace App\Repositories\V1\Document;

use App\Models\V1\Document\Document;
use App\Repositories\V1\Document\Interfaces\DocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DocumentRepository implements DocumentRepositoryInterface
{
    /**
     * Get all documents for an entity.
     */
    public function getForEntity(Model $entity, ?string $type = null): Collection
    {
        $query = Document::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->id);

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->latest()->get();
    }

    /**
     * Find document by ID.
     */
    public function find(int $id): ?Document
    {
        return Document::find($id);
    }

    /**
     * Create a new document.
     */
    public function create(array $data): Document
    {
        return Document::create($data);
    }

    /**
     * Update document.
     */
    public function update(Document $document, array $data): Document
    {
        $document->update($data);
        return $document->fresh();
    }

    /**
     * Delete document.
     */
    public function delete(Document $document): bool
    {
        return $document->delete();
    }

    /**
     * Get expired documents.
     */
    public function getExpired(): Collection
    {
        return Document::expired()->get();
    }
}

