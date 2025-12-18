<?php

namespace App\Services\V1\Tenant;

use App\Mail\V1\Tenant\TenantInvitationMail;
use App\Models\V1\Auth\Role;
use App\Models\V1\Auth\User;
use App\Models\V1\Tenant\Tenant;
use App\Models\V1\Tenant\TenantInvitation;
use App\Repositories\V1\Tenant\Interfaces\TenantInvitationRepositoryInterface;
use App\Repositories\V1\Tenant\Interfaces\TenantRepositoryInterface;
use App\Services\V1\Auth\AuthService;
use App\Services\V1\Mail\OwnershipMailService;
use App\Services\V1\Notification\NotificationService;
use App\Services\V1\Ownership\UserOwnershipMappingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantInvitationService
{
    public function __construct(
        private TenantInvitationRepositoryInterface $invitationRepository,
        private TenantRepositoryInterface $tenantRepository,
        private AuthService $authService,
        private UserOwnershipMappingService $mappingService,
        private OwnershipMailService $mailService,
        private NotificationService $notificationService
    ) {}

    /**
     * Get all invitations with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->invitationRepository->paginate($perPage, $filters);
    }

    /**
     * Get all invitations.
     */
    public function all(array $filters = []): Collection
    {
        return $this->invitationRepository->all($filters);
    }

    /**
     * Find invitation by ID.
     */
    public function find(int $id): ?TenantInvitation
    {
        return $this->invitationRepository->find($id);
    }

    /**
     * Find invitation by UUID.
     */
    public function findByUuid(string $uuid): ?TenantInvitation
    {
        return $this->invitationRepository->findByUuid($uuid);
    }

    /**
     * Find invitation by token.
     */
    public function findByToken(string $token): ?TenantInvitation
    {
        return $this->invitationRepository->findByToken($token);
    }

    /**
     * Create a single invitation.
     */
    public function create(array $data): TenantInvitation
    {
        return DB::transaction(function () use ($data) {
            // Generate secure token
            $token = $this->generateToken();

            // Set expiration (default: 7 days)
            $expiresInDays = $data['expires_in_days'] ?? 7;
            $expiresAt = now()->addDays($expiresInDays);

            // Create invitation
            $invitation = $this->invitationRepository->create([
                'ownership_id' => $data['ownership_id'],
                'invited_by' => $data['invited_by'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'name' => $data['name'] ?? null,
                'token' => $token,
                'status' => 'pending',
                'expires_at' => $expiresAt,
                'notes' => $data['notes'] ?? null,
            ]);

            // Send email if email provided
            if ($invitation->email) {
                $this->sendInvitationEmail($invitation);
            }

            // TODO: Send SMS if phone provided (when SMS service ready)

            // Load relationships before sending notifications
            $invitation->load(['ownership', 'invitedBy']);

            // Send system notifications to users with permission
            $this->notifyInvitationCreated($invitation);

            return $invitation;
        });
    }

    /**
     * Create multiple invitations (bulk).
     */
    public function createBulk(array $invitationsData, int $ownershipId, int $invitedBy): Collection
    {
        return DB::transaction(function () use ($invitationsData, $ownershipId, $invitedBy) {
            $created = [];

            foreach ($invitationsData as $data) {
                $data['ownership_id'] = $ownershipId;
                $data['invited_by'] = $invitedBy;
                $invitation = $this->create($data);
                $created[] = $invitation;
            }

            // Convert array to Eloquent Collection
            return new Collection($created);
        });
    }

    /**
     * Generate invitation link (without sending email).
     */
    public function generateLink(array $data): TenantInvitation
    {
        return DB::transaction(function () use ($data) {
            // Generate secure token
            $token = $this->generateToken();

            // Set expiration (default: 7 days)
            $expiresInDays = $data['expires_in_days'] ?? 7;
            $expiresAt = now()->addDays($expiresInDays);

            // Create invitation
            $invitation = $this->invitationRepository->create([
                'ownership_id' => $data['ownership_id'],
                'invited_by' => $data['invited_by'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'name' => $data['name'] ?? null,
                'token' => $token,
                'status' => 'pending',
                'expires_at' => $expiresAt,
                'notes' => $data['notes'] ?? null,
            ]);

            return $invitation->load(['ownership', 'invitedBy']);
        });
    }

    /**
     * Resend invitation email.
     */
    public function resendInvitation(TenantInvitation $invitation): bool
    {
        if (!$invitation->email) {
            return false;
        }

        if ($invitation->isExpired() || $invitation->isAccepted() || $invitation->isCancelled()) {
            return false;
        }

        try {
            $this->sendInvitationEmail($invitation);
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to resend invitation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel invitation.
     */
    public function cancel(TenantInvitation $invitation): TenantInvitation
    {
        return DB::transaction(function () use ($invitation) {
            $invitation->cancel();
            return $invitation->fresh(['ownership', 'invitedBy']);
        });
    }

    /**
     * Accept invitation and create tenant.
     */
    public function acceptInvitation(string $token, array $registrationData): array
    {
        return DB::transaction(function () use ($token, $registrationData) {
            // Find invitation
            $invitation = $this->invitationRepository->findByToken($token);

            if (!$invitation) {
                throw new \Exception('Invalid invitation token.');
            }

            // Validate invitation
            if ($invitation->isExpired()) {
                throw new \Exception('Invitation has expired.');
            }

            if ($invitation->isCancelled()) {
                throw new \Exception('Invitation has been cancelled.');
            }

            // For invitations WITH email/phone: single-use (check if already accepted)
            // For invitations WITHOUT email/phone: multi-use (allow multiple tenants)
            if ($invitation->email || $invitation->phone) {
                if ($invitation->isAccepted()) {
                    throw new \Exception('Invitation has already been accepted.');
                }
            }

            // Validate email matches (if email was provided in invitation)
            if ($invitation->email && $invitation->email !== $registrationData['email']) {
                throw new \Exception('Email does not match invitation.');
            }

            // Check if user already exists
            $user = \App\Models\V1\Auth\User::where('email', $registrationData['email'])->first();

            if (!$user) {
                // Create new user with tenant type
                $registerResult = $this->authService->register([
                    'email' => $registrationData['email'],
                    'phone' => $registrationData['phone'] ?? null,
                    'first' => $registrationData['first_name'],
                    'last' => $registrationData['last_name'],
                    'password' => $registrationData['password'],
                    'password_confirmation' => $registrationData['password_confirmation'],
                    'type' => 'tenant', // Set user type to tenant
                    'device_name' => 'web',
                ]);
                $user = $registerResult['user'];
                
                // Assign Tenant role
                $tenantRole = Role::where('name', 'Tenant')->first();
                if ($tenantRole) {
                    $user->assignRole($tenantRole);
                }
            } else {
                // Check if tenant already exists for this ownership
                $existingTenant = $this->tenantRepository->findByUserAndOwnership(
                    $user->id,
                    $invitation->ownership_id
                );

                if ($existingTenant) {
                    throw new \Exception('Tenant already exists for this ownership.');
                }
                
                // If user exists but doesn't have tenant type/role, update it
                if ($user->type !== 'tenant') {
                    $user->update(['type' => 'tenant']);
                }
                
                // Ensure user has Tenant role
                if (!$user->hasRole('Tenant')) {
                    $tenantRole = Role::where('name', 'Tenant')->first();
                    if ($tenantRole) {
                        $user->assignRole($tenantRole);
                    }
                }
            }

            // Create tenant profile
            $tenant = $this->tenantRepository->create([
                'user_id' => $user->id,
                'ownership_id' => $invitation->ownership_id,
                'invitation_id' => $invitation->id, // Track which invitation created this tenant
                'national_id' => $registrationData['national_id'] ?? null,
                'id_type' => $registrationData['id_type'] ?? 'national_id',
                'id_expiry' => $registrationData['id_expiry'] ?? null,
                'emergency_name' => $registrationData['emergency_name'] ?? null,
                'emergency_phone' => $registrationData['emergency_phone'] ?? null,
                'emergency_relation' => $registrationData['emergency_relation'] ?? null,
                'employment' => $registrationData['employment'] ?? null,
                'employer' => $registrationData['employer'] ?? null,
                'income' => $registrationData['income'] ?? null,
                'rating' => $registrationData['rating'] ?? 'good',
                'notes' => $registrationData['notes'] ?? null,
            ]);

            // Link user to ownership (if not already linked)
            try {
                $existingMapping = $this->mappingService->findByUserAndOwnership(
                    $user->id,
                    $invitation->ownership_id
                );

                if (!$existingMapping) {
                    // Check if this is user's first ownership mapping (set as default)
                    $userMappings = $this->mappingService->getByUser($user->id);
                    $isDefault = $userMappings->isEmpty();

                    $this->mappingService->create([
                        'user_id' => $user->id,
                        'ownership_id' => $invitation->ownership_id,
                        'default' => $isDefault,
                    ]);
                }
            } catch (\Exception $e) {
                // If mapping already exists or creation fails, log but don't fail the registration
                Log::warning("Failed to create ownership mapping for tenant registration: " . $e->getMessage());
            }

            // Load relationships before notifications
            $invitation->load(['ownership']);
            $tenant->load(['user']);

            // Mark invitation as accepted ONLY if it has email/phone (single-use)
            // Invitations without email/phone remain pending for multiple acceptances
            if ($invitation->email || $invitation->phone) {
                // Single-use invitation: mark as accepted
                $invitation->accept($user->id);
                $invitation->update(['tenant_id' => $tenant->id]);
                
                // Notify users about acceptance (single-use)
                $this->notifyInvitationAccepted($invitation, $tenant);
            } else {
                // Multi-use invitation: keep pending, don't set accepted_by or tenant_id
                // Owner must manually close it when done
                // Just update timestamp to track last acceptance
                $invitation->touch(); // Update updated_at timestamp
                
                // Notify users about new tenant joining (multi-use)
                $this->notifyTenantJoined($invitation, $tenant);
            }

            // Generate tokens for user
            $tokens = $user->generateTokens('web');

            return [
                'user' => $user,
                'tenant' => $tenant,
                'invitation' => $invitation->fresh(['ownership']),
                'tokens' => $tokens,
            ];
        });
    }

    /**
     * Generate secure token.
     */
    private function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while ($this->invitationRepository->findByToken($token) !== null);

        return $token;
    }

    /**
     * Send invitation email using ownership-specific mail configuration.
     */
    private function sendInvitationEmail(TenantInvitation $invitation): void
    {
        try {
            // Use ownership-specific mailer if configured, otherwise use default
            $this->mailService->sendForOwnership(
                $invitation->ownership_id,
                $invitation->email,
                new TenantInvitationMail($invitation)
            );
        } catch (\Exception $e) {
            Log::error("Failed to send invitation email: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get users who should receive notifications about invitations for an ownership.
     */
    private function getUsersToNotify(int $ownershipId): Collection
    {
        // Get all users mapped to this ownership
        $mappings = $this->mappingService->getByOwnership($ownershipId);
        $userIds = $mappings->pluck('user_id')->unique();
        
        // Filter users who have the notification permission
        return User::whereIn('id', $userIds)
            ->get()
            ->filter(function ($user) use ($ownershipId) {
                // Check permission and ownership access directly
                // Super Admin can receive notifications if they have permission
                if ($user->isSuperAdmin()) {
                    return $user->can('tenants.invitations.notifications');
                }

                // Check permission and ownership access
                return $user->can('tenants.invitations.notifications') 
                    && $user->hasOwnership($ownershipId);
            });
    }

    /**
     * Notify users when invitation is created.
     */
    private function notifyInvitationCreated(TenantInvitation $invitation): void
    {
        $usersToNotify = $this->getUsersToNotify($invitation->ownership_id);
        
        foreach ($usersToNotify as $user) {
            try {
                $this->notificationService->create([
                    'user_id' => $user->id,
                    'type' => 'info',
                    'title' => __('notifications.tenant_invitation.created.title'),
                    'message' => __('notifications.tenant_invitation.created.message', [
                        'email' => $invitation->email ?? __('notifications.tenant_invitation.no_email'),
                        'phone' => $invitation->phone ?? __('notifications.tenant_invitation.no_phone'),
                        'name' => $invitation->name ?? __('notifications.tenant_invitation.no_name'),
                        'ownership' => $invitation->ownership->name,
                        'invited_by' => $invitation->invitedBy->name,
                    ]),
                    'category' => 'tenant_invitation',
                    'action_url' => '/tenants/invitations/' . $invitation->uuid,
                    'action_text' => __('notifications.tenant_invitation.view_invitation'),
                    'data' => [
                        'invitation_uuid' => $invitation->uuid,
                        'ownership_id' => $invitation->ownership_id,
                        'invited_by' => $invitation->invited_by,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send notification for invitation creation: " . $e->getMessage());
            }
        }
    }

    /**
     * Notify users when invitation is accepted (single-use).
     */
    private function notifyInvitationAccepted(TenantInvitation $invitation, Tenant $tenant): void
    {
        $usersToNotify = $this->getUsersToNotify($invitation->ownership_id);
        
        foreach ($usersToNotify as $user) {
            try {
                $this->notificationService->create([
                    'user_id' => $user->id,
                    'type' => 'success',
                    'title' => __('notifications.tenant_invitation.accepted.title'),
                    'message' => __('notifications.tenant_invitation.accepted.message', [
                        'tenant_name' => $tenant->user->name,
                        'tenant_email' => $tenant->user->email,
                        'ownership' => $invitation->ownership->name,
                    ]),
                    'category' => 'tenant_invitation',
                    'action_url' => '/tenants/' . $tenant->id,
                    'action_text' => __('notifications.tenant_invitation.view_tenant'),
                    'data' => [
                        'invitation_uuid' => $invitation->uuid,
                        'tenant_id' => $tenant->id,
                        'ownership_id' => $invitation->ownership_id,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send notification for invitation acceptance: " . $e->getMessage());
            }
        }
    }

    /**
     * Notify users when tenant joins via multi-use invitation.
     */
    private function notifyTenantJoined(TenantInvitation $invitation, Tenant $tenant): void
    {
        $usersToNotify = $this->getUsersToNotify($invitation->ownership_id);
        
        foreach ($usersToNotify as $user) {
            try {
                $this->notificationService->create([
                    'user_id' => $user->id,
                    'type' => 'success',
                    'title' => __('notifications.tenant_invitation.tenant_joined.title'),
                    'message' => __('notifications.tenant_invitation.tenant_joined.message', [
                        'tenant_name' => $tenant->user->name,
                        'tenant_email' => $tenant->user->email,
                        'ownership' => $invitation->ownership->name,
                        'total_tenants' => $invitation->tenants()->count(),
                    ]),
                    'category' => 'tenant_invitation',
                    'action_url' => '/tenants/invitations/' . $invitation->uuid,
                    'action_text' => __('notifications.tenant_invitation.view_invitation'),
                    'data' => [
                        'invitation_uuid' => $invitation->uuid,
                        'tenant_id' => $tenant->id,
                        'ownership_id' => $invitation->ownership_id,
                        'tenants_count' => $invitation->tenants()->count(),
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send notification for tenant join: " . $e->getMessage());
            }
        }
    }

    /**
     * Mark expired invitations.
     */
    public function expireOldInvitations(): int
    {
        $expired = $this->invitationRepository->getExpiredInvitations();
        $count = 0;

        foreach ($expired as $invitation) {
            $this->invitationRepository->markAsExpired($invitation);
            $count++;
        }

        return $count;
    }
}

