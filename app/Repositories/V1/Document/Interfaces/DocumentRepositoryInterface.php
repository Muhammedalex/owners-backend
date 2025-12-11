<?php

namespace App\Repositories\V1\Document\Interfaces;

use App\Models\V1\Document\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface DocumentRepositoryInterface
{
    /**
     * Get all documents for an entity.
     */
    public function getForEntity(Model $entity, ?string $type = null): Collection;

    /**
     * Find document by ID.
     */
    public function find(int $id): ?Document;

    /**
     * Create a new document.
     */
    public function create(array $data): Document;

    /**
     * Update document.
     */
    public function update(Document $document, array $data): Document;

    /**
     * Delete document.
     */
    public function delete(Document $document): bool;

    /**
     * Get expired documents.
     */
    public function getExpired(): Collection;
}

