<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Tenant\AcceptTenantInvitationRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Http\Resources\V1\Tenant\TenantResource;
use App\Models\V1\Tenant\TenantInvitation;
use App\Services\V1\Tenant\TenantInvitationService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicTenantInvitationController extends Controller
{
    use HasLocalizedResponse;

    public function __construct(
        private TenantInvitationService $invitationService
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

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'tenant' => new TenantResource($result['tenant']->load(['user', 'ownership'])),
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
}

