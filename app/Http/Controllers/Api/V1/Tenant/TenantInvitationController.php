<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Tenant\StoreBulkTenantInvitationRequest;
use App\Http\Requests\V1\Tenant\StoreTenantInvitationRequest;
use App\Http\Resources\V1\Tenant\TenantInvitationResource;
use App\Models\V1\Tenant\TenantInvitation;
use App\Services\V1\Tenant\TenantInvitationService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantInvitationController extends Controller
{
    use HasLocalizedResponse;

    public function __construct(
        private TenantInvitationService $invitationService
    ) {}

    /**
     * Display a listing of invitations.
     * Ownership scope is mandatory from middleware.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TenantInvitation::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            ['ownership_id' => $ownershipId], // MANDATORY
            $request->only(['search', 'status', 'pending', 'expired', 'accepted'])
        );

        if ($perPage === -1) {
            $invitations = $this->invitationService->all($filters);

            return $this->successResponse(
                TenantInvitationResource::collection($invitations)
            );
        }

        $invitations = $this->invitationService->paginate($perPage, $filters);

        return $this->successResponse(
            TenantInvitationResource::collection($invitations->items()),
            null,
            200,
            [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
            ]
        );
    }

    /**
     * Store a newly created invitation (single).
     * Ownership scope is mandatory from middleware.
     */
    public function store(StoreTenantInvitationRequest $request): JsonResponse
    {
        $this->authorize('create', TenantInvitation::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $data = $request->validated();
        $data['ownership_id'] = $ownershipId; // MANDATORY
        $data['invited_by'] = $request->user()->id;

        $invitation = $this->invitationService->create($data);

        return $this->successResponse(
            new TenantInvitationResource($invitation),
            'tenants.invitations.created',
            201
        );
    }

    /**
     * Store multiple invitations (bulk).
     * Ownership scope is mandatory from middleware.
     */
    public function storeBulk(StoreBulkTenantInvitationRequest $request): JsonResponse
    {
        $this->authorize('create', TenantInvitation::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $data = $request->validated();
        $invitations = $this->invitationService->createBulk(
            $data['invitations'],
            $ownershipId,
            $request->user()->id
        );

        return $this->successResponse(
            TenantInvitationResource::collection($invitations),
            'tenants.invitations.bulk_created',
            201
        );
    }

    /**
     * Generate invitation link (without sending email).
     * Ownership scope is mandatory from middleware.
     */
    public function generateLink(Request $request): JsonResponse
    {
        $this->authorize('create', TenantInvitation::class);

        // Get ownership ID from middleware (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $data = $request->only(['email', 'phone', 'name', 'expires_in_days', 'notes']);
        $data['ownership_id'] = $ownershipId;
        $data['invited_by'] = $request->user()->id;

        $invitation = $this->invitationService->generateLink($data);

        return $this->successResponse(
            new TenantInvitationResource($invitation),
            'tenants.invitations.link_generated',
            201
        );
    }

    /**
     * Display the specified invitation.
     * Ownership scope is mandatory from middleware.
     */
    public function show(Request $request, TenantInvitation $invitation): JsonResponse
    {
        $this->authorize('view', $invitation);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invitation->ownership_id != $ownershipId) {
            return $this->notFoundResponse('tenants.invitations.not_found');
        }

        $invitation = $this->invitationService->findByUuid($invitation->uuid);

        if (!$invitation) {
            return $this->notFoundResponse('tenants.invitations.not_found');
        }

        // Load relationships: tenant (for single-use) and tenants (for multi-use)
        $invitation->load(['tenant', 'tenants.user']);

        return $this->successResponse(new TenantInvitationResource($invitation));
    }

    /**
     * Resend invitation email.
     * Ownership scope is mandatory from middleware.
     */
    public function resend(Request $request, TenantInvitation $invitation): JsonResponse
    {
        $this->authorize('resend', $invitation);

        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invitation->ownership_id != $ownershipId) {
            return $this->notFoundResponse('tenants.invitations.not_found');
        }

        $success = $this->invitationService->resendInvitation($invitation);

        if (!$success) {
            return $this->errorResponse('tenants.invitations.resend_failed', 400);
        }

        return $this->successResponse(
            new TenantInvitationResource($invitation->fresh()),
            'tenants.invitations.resent'
        );
    }

    /**
     * Cancel invitation.
     * Ownership scope is mandatory from middleware.
     * For invitations without email/phone, requires special permission.
     */
    public function cancel(Request $request, TenantInvitation $invitation): JsonResponse
    {
        // Verify ownership scope (MANDATORY)
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $invitation->ownership_id != $ownershipId) {
            return $this->notFoundResponse('tenants.invitations.not_found');
        }

        // Check if invitation has no email/phone - requires special permission
        if (!$invitation->email && !$invitation->phone) {
            $this->authorize('closeWithoutContact', $invitation);
        } else {
            $this->authorize('cancel', $invitation);
        }

        $invitation = $this->invitationService->cancel($invitation);

        return $this->successResponse(
            new TenantInvitationResource($invitation),
            'tenants.invitations.cancelled'
        );
    }
}

