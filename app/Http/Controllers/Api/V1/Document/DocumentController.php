<?php

namespace App\Http\Controllers\Api\V1\Document;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Document\DocumentResource;
use App\Models\V1\Document\Document;
use App\Services\V1\Document\DocumentService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentController extends Controller
{
    use HasLocalizedResponse;
    public function __construct(
        private DocumentService $documentService
    ) {}

    /**
     * Upload document for an entity.
     * 
     * POST /api/v1/documents/upload
     */
    public function upload(Request $request): JsonResponse
    {
        Gate::authorize('create', Document::class);

        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'max:50'],
            'file' => ['required', 'file'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'public' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        // Resolve entity
        $entity = $this->resolveEntity($validated['entity_type'], $validated['entity_id']);
        if (!$entity) {
            return $this->notFoundResponse('messages.errors.entity_not_found');
        }

        // Check ownership access
        if (!$this->checkOwnershipAccess($entity, $ownershipId, $request->user())) {
            return $this->forbiddenResponse('messages.errors.no_access');
        }

        try {
            $document = $this->documentService->upload(
                entity: $entity,
                file: $request->file('file'),
                type: $validated['type'],
                ownershipId: $ownershipId,
                title: $validated['title'],
                uploadedBy: $request->user()->id,
                description: $validated['description'] ?? null,
                public: $validated['public'] ?? false,
                expiresAt: isset($validated['expires_at']) && $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null
            );

            return $this->successResponse(
                new DocumentResource($document),
                'documents.uploaded',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('messages.errors.file_upload_failed', 400, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get documents for an entity.
     * 
     * GET /api/v1/documents
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Document::class);

        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
            'type' => ['nullable', 'string'],
        ]);

        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        // Resolve entity
        $entity = $this->resolveEntity($validated['entity_type'], $validated['entity_id']);
        if (!$entity) {
            return $this->notFoundResponse('messages.errors.entity_not_found');
        }

        // Check ownership access
        if (!$this->checkOwnershipAccess($entity, $ownershipId, $request->user())) {
            return $this->forbiddenResponse('messages.errors.no_access');
        }

        $documents = Document::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->id)
            ->where('ownership_id', $ownershipId)
            ->when($validated['type'] ?? null, fn($q, $type) => $q->where('type', $type))
            ->latest()
            ->get();

        return $this->successResponse(DocumentResource::collection($documents));
    }

    /**
     * Show document.
     * 
     * GET /api/v1/documents/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        
        if (!$document) {
            return $this->notFoundResponse('documents.not_found');
        }

        Gate::authorize('view', $document);

        return $this->successResponse(new DocumentResource($document));
    }

    /**
     * Download document.
     * 
     * GET /api/v1/documents/{id}/download
     */
    public function download(Request $request, int $id)
    {
        $document = Document::find($id);
        
        if (!$document) {
            abort(404, __('documents.not_found'));
        }

        Gate::authorize('view', $document);

        return $this->documentService->download($document);
    }

    /**
     * Update document.
     * 
     * PUT /api/v1/documents/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        
        if (!$document) {
            return $this->notFoundResponse('documents.not_found');
        }

        Gate::authorize('update', $document);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'public' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        if (isset($validated['expires_at'])) {
            $validated['expires_at'] = \Carbon\Carbon::parse($validated['expires_at']);
        }

        $document = $this->documentService->update($document, $validated);

        return $this->successResponse(
            new DocumentResource($document),
            'documents.updated'
        );
    }

    /**
     * Delete document.
     * 
     * DELETE /api/v1/documents/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);
        
        if (!$document) {
            return $this->notFoundResponse('documents.not_found');
        }

        Gate::authorize('delete', $document);

        $this->documentService->delete($document);

        return $this->successResponse(null, 'documents.deleted');
    }

    /**
     * Resolve entity from type and ID.
     */
    private function resolveEntity(string $entityType, int $entityId)
    {
        // Map entity types to models
        $entityMap = [
            'ownership' => \App\Models\V1\Ownership\Ownership::class,
            'user' => \App\Models\V1\Auth\User::class,
            'tenant' => \App\Models\V1\Tenant\Tenant::class,
            'contract' => \App\Models\V1\Contract\Contract::class,
            'invoice' => \App\Models\V1\Invoice\Invoice::class,
            'payment' => \App\Models\V1\Payment\Payment::class,
            'building' => \App\Models\V1\Ownership\Building::class,
            'unit' => \App\Models\V1\Ownership\Unit::class,
            'portfolio' => \App\Models\V1\Ownership\Portfolio::class,
        ];

        $modelClass = $entityMap[$entityType] ?? $entityType;
        
        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($entityId);
    }

    /**
     * Check ownership access.
     */
    private function checkOwnershipAccess($entity, int $ownershipId, $user): bool
    {
        // Super Admin has access to all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if entity has ownership_id field
        if (isset($entity->ownership_id)) {
            return $entity->ownership_id == $ownershipId && $user->hasOwnership($ownershipId);
        }

        // For entities without direct ownership_id, check through relationships
        return $user->hasOwnership($ownershipId);
    }
}

