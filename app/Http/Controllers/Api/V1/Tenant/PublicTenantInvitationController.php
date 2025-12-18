<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Tenant\AcceptTenantInvitationRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Http\Resources\V1\Tenant\TenantResource;
use App\Models\V1\Tenant\TenantInvitation;
use App\Services\V1\Media\MediaService;
use App\Services\V1\Tenant\TenantInvitationService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicTenantInvitationController extends Controller
{
    use HasLocalizedResponse;

    public function __construct(
        private TenantInvitationService $invitationService,
        private MediaService $mediaService
    ) {}

    /**
     * Validate invitation token (public endpoint).
     */
    public function validateToken(Request $request, string $token): JsonResponse
    {
        $invitation = $this->invitationService->findByToken($token);

        if (!$invitation) {
            return $this->errorResponse('tenants.invitations.invalid_token', 404);
        }

        if ($invitation->isExpired()) {
            return $this->errorResponse('tenants.invitations.expired', 400);
        }

        if ($invitation->isAccepted()) {
            return $this->errorResponse('tenants.invitations.already_accepted', 400);
        }

        if ($invitation->isCancelled()) {
            return $this->errorResponse('tenants.invitations.cancelled', 400);
        }

        // For invitations WITH email/phone: check if already accepted (single-use)
        // For invitations WITHOUT email/phone: allow multiple acceptances (multi-use)
        if (($invitation->email || $invitation->phone) && $invitation->isAccepted()) {
            return $this->errorResponse('tenants.invitations.already_accepted', 400);
        }

        return $this->successResponse([
            'valid' => true,
            'invitation' => [
                'email' => $invitation->email,
                'name' => $invitation->name,
                'ownership' => [
                    'name' => $invitation->ownership->name,
                ],
                'expires_at' => $invitation->expires_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Accept invitation and register tenant (public endpoint).
     */
    public function accept(AcceptTenantInvitationRequest $request, string $token): JsonResponse
    {
        try {
            $result = $this->invitationService->acceptInvitation($token, $request->validated());
            
            $tenant = $result['tenant'];
            $ownershipId = $tenant->ownership_id;
            
            // Handle media uploads (ID, commercial registration, municipality license)
            $this->handleMediaUploads($request, $tenant, $ownershipId);
            
            // Refresh tenant to get latest media files
            $tenant->refresh();

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'tenant' => new TenantResource($tenant->load(['user', 'ownership', 'mediaFiles'])),
                'invitation' => [
                    'uuid' => $result['invitation']->uuid,
                    'status' => $result['invitation']->status,
                ],
                'access_token' => $result['tokens']['access_token'],
                'redirect_to' => '/dashboard',
            ], 'tenants.invitations.accepted', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Handle media uploads for tenant on invitation accept.
     * Deletes old images before uploading new ones (if updating existing tenant).
     */
    private function handleMediaUploads(Request $request, $tenant, int $ownershipId): void
    {
        // For new tenant registration, there are no old images to delete
        // But we still check in case tenant already exists

        // ID Document Image
        if ($request->hasFile('id_document_image')) {
            $file = $request->file('id_document_image');
            
            // Delete old ID document image if exists (for existing tenants)
            $oldImage = $tenant->mediaFiles()
                ->where('type', 'tenant_id_document')
                ->first();
            
            if ($oldImage) {
                $this->mediaService->delete($oldImage);
            }

            // Upload new image
            $this->mediaService->upload(
                entity: $tenant,
                file: $file,
                type: 'tenant_id_document',
                ownershipId: $ownershipId,
                uploadedBy: $tenant->user_id, // Use tenant's user ID
            );
        }

        // Commercial Registration Image
        if ($request->hasFile('commercial_registration_image')) {
            $file = $request->file('commercial_registration_image');
            
            // Delete old commercial registration image if exists
            $oldImage = $tenant->mediaFiles()
                ->where('type', 'tenant_cr_document')
                ->first();
            
            if ($oldImage) {
                $this->mediaService->delete($oldImage);
            }

            // Upload new image
            $this->mediaService->upload(
                entity: $tenant,
                file: $file,
                type: 'tenant_cr_document',
                ownershipId: $ownershipId,
                uploadedBy: $tenant->user_id,
            );
        }

        // Municipality License Image
        if ($request->hasFile('municipality_license_image')) {
            $file = $request->file('municipality_license_image');
            
            // Delete old municipality license image if exists
            $oldImage = $tenant->mediaFiles()
                ->where('type', 'tenant_municipality_license')
                ->first();
            
            if ($oldImage) {
                $this->mediaService->delete($oldImage);
            }

            // Upload new image
            $this->mediaService->upload(
                entity: $tenant,
                file: $file,
                type: 'tenant_municipality_license',
                ownershipId: $ownershipId,
                uploadedBy: $tenant->user_id,
            );
        }
    }
}

