<?php

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Media\MediaFileResource;
use App\Models\V1\Media\MediaFile;
use App\Services\V1\Media\MediaService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MediaFileController extends Controller
{
    use HasLocalizedResponse;
    public function __construct(
        private MediaService $mediaService
    ) {}

    /**
     * Upload media file for an entity.
     * 
     * POST /api/v1/media/upload
     */
    public function upload(Request $request): JsonResponse
    {
        Gate::authorize('create', MediaFile::class);

        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'max:50'],
            'file' => ['required', 'file'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'public' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer'],
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
            $mediaFile = $this->mediaService->upload(
                entity: $entity,
                file: $request->file('file'),
                type: $validated['type'],
                ownershipId: $ownershipId,
                uploadedBy: $request->user()->id,
                title: $validated['title'] ?? null,
                description: $validated['description'] ?? null,
                public: $validated['public'] ?? true,
                order: $validated['order'] ?? null
            );

            return $this->successResponse(
                new MediaFileResource($mediaFile),
                'media.uploaded',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('messages.errors.file_upload_failed', 400, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get media files for an entity.
     * 
     * GET /api/v1/media
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MediaFile::class);

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

        $mediaFiles = MediaFile::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->id)
            ->where('ownership_id', $ownershipId)
            ->when($validated['type'] ?? null, fn($q, $type) => $q->where('type', $type))
            ->ordered()
            ->get();

        return $this->successResponse(MediaFileResource::collection($mediaFiles));
    }

    /**
     * Show media file.
     * 
     * GET /api/v1/media/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $mediaFile = MediaFile::find($id);
        
        if (!$mediaFile) {
            return $this->notFoundResponse('media.not_found');
        }

        Gate::authorize('view', $mediaFile);

        return $this->successResponse(new MediaFileResource($mediaFile));
    }

    /**
     * Update media file.
     * 
     * PUT /api/v1/media/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $mediaFile = MediaFile::find($id);
        
        if (!$mediaFile) {
            return $this->notFoundResponse('media.not_found');
        }

        Gate::authorize('update', $mediaFile);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'public' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer'],
        ]);

        $mediaFile = $this->mediaService->update($mediaFile, $validated);

        return $this->successResponse(
            new MediaFileResource($mediaFile),
            'media.updated'
        );
    }

    /**
     * Delete media file.
     * 
     * DELETE /api/v1/media/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $mediaFile = MediaFile::find($id);
        
        if (!$mediaFile) {
            return $this->notFoundResponse('media.not_found');
        }

        Gate::authorize('delete', $mediaFile);

        $this->mediaService->delete($mediaFile);

        return $this->successResponse(null, 'media.deleted');
    }

    /**
     * Reorder media files.
     * 
     * POST /api/v1/media/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        Gate::authorize('update', MediaFile::class);

        $validated = $request->validate([
            'media_file_ids' => ['required', 'array', 'min:1'],
            'media_file_ids.*' => ['required', 'integer', 'exists:media_files,id'],
        ]);

        $this->mediaService->reorder($validated['media_file_ids']);

        return $this->successResponse(null, 'messages.success.updated');
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
        // This is a simplified check - you may need to adjust based on your models
        return $user->hasOwnership($ownershipId);
    }
}

