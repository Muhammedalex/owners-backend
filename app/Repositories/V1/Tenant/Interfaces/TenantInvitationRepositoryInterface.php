<?php

namespace App\Repositories\V1\Tenant\Interfaces;

use App\Models\V1\Tenant\TenantInvitation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TenantInvitationRepositoryInterface
{
    /**
     * Get all invitations with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all invitations.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find invitation by ID.
     */
    public function find(int $id): ?TenantInvitation;

    /**
     * Find invitation by UUID.
     */
    public function findByUuid(string $uuid): ?TenantInvitation;

    /**
     * Find invitation by token.
     */
    public function findByToken(string $token): ?TenantInvitation;

    /**
     * Find invitation by email.
     */
    public function findByEmail(string $email, int $ownershipId): ?TenantInvitation;

    /**
     * Create a new invitation.
     */
    public function create(array $data): TenantInvitation;

    /**
     * Update invitation.
     */
    public function update(TenantInvitation $invitation, array $data): TenantInvitation;

    /**
     * Delete invitation.
     */
    public function delete(TenantInvitation $invitation): bool;

    /**
     * Mark invitation as expired.
     */
    public function markAsExpired(TenantInvitation $invitation): bool;

    /**
     * Get expired invitations.
     */
    public function getExpiredInvitations(): Collection;
}

