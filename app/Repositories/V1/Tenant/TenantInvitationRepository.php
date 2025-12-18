<?php

namespace App\Repositories\V1\Tenant;

use App\Models\V1\Tenant\TenantInvitation;
use App\Repositories\V1\Tenant\Interfaces\TenantInvitationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TenantInvitationRepository implements TenantInvitationRepositoryInterface
{
    /**
     * Get all invitations with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = TenantInvitation::query();

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('token', 'like', "%{$search}%");
            });
        }

        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['pending'])) {
            $query->pending();
        }

        if (isset($filters['expired'])) {
            $query->expired();
        }

        if (isset($filters['accepted'])) {
            $query->accepted();
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['ownership', 'invitedBy', 'acceptedBy', 'tenant'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all invitations.
     */
    public function all(array $filters = []): Collection
    {
        $query = TenantInvitation::query();

        // Apply filters (same as paginate)
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('token', 'like', "%{$search}%");
            });
        }

        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['pending'])) {
            $query->pending();
        }

        if (isset($filters['expired'])) {
            $query->expired();
        }

        if (isset($filters['accepted'])) {
            $query->accepted();
        }

        // Filter by ownership IDs (for non-Super Admin users)
        if (isset($filters['ownership_ids']) && is_array($filters['ownership_ids']) && !empty($filters['ownership_ids'])) {
            $query->whereIn('ownership_id', $filters['ownership_ids']);
        }

        return $query->with(['ownership', 'invitedBy', 'acceptedBy', 'tenant'])
            ->latest()
            ->get();
    }

    /**
     * Find invitation by ID.
     */
    public function find(int $id): ?TenantInvitation
    {
        return TenantInvitation::with(['ownership', 'invitedBy', 'acceptedBy', 'tenant'])->find($id);
    }

    /**
     * Find invitation by UUID.
     */
    public function findByUuid(string $uuid): ?TenantInvitation
    {
        return TenantInvitation::with(['ownership', 'invitedBy', 'acceptedBy', 'tenant'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Find invitation by token.
     */
    public function findByToken(string $token): ?TenantInvitation
    {
        return TenantInvitation::with(['ownership', 'invitedBy'])
            ->where('token', $token)
            ->first();
    }

    /**
     * Find invitation by email.
     */
    public function findByEmail(string $email, int $ownershipId): ?TenantInvitation
    {
        return TenantInvitation::where('email', $email)
            ->where('ownership_id', $ownershipId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Create a new invitation.
     */
    public function create(array $data): TenantInvitation
    {
        return TenantInvitation::create($data);
    }

    /**
     * Update invitation.
     */
    public function update(TenantInvitation $invitation, array $data): TenantInvitation
    {
        $invitation->update($data);
        return $invitation->fresh(['ownership', 'invitedBy', 'acceptedBy', 'tenant']);
    }

    /**
     * Delete invitation.
     */
    public function delete(TenantInvitation $invitation): bool
    {
        return $invitation->delete();
    }

    /**
     * Mark invitation as expired.
     */
    public function markAsExpired(TenantInvitation $invitation): bool
    {
        return $invitation->markAsExpired();
    }

    /**
     * Get expired invitations.
     */
    public function getExpiredInvitations(): Collection
    {
        return TenantInvitation::expired()->get();
    }
}

