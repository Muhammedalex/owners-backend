<?php

namespace App\Repositories\V1\Ownership;

use App\Models\V1\Ownership\OwnershipBoardMember;
use App\Repositories\V1\Ownership\Interfaces\OwnershipBoardMemberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OwnershipBoardMemberRepository implements OwnershipBoardMemberRepositoryInterface
{
    /**
     * Get all board members with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = OwnershipBoardMember::query();

        // Apply filters
        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['role'])) {
            $query->byRole($filters['role']);
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        return $query->with(['ownership', 'user'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all board members.
     */
    public function all(array $filters = []): Collection
    {
        $query = OwnershipBoardMember::query();

        // Apply filters
        if (isset($filters['ownership_id'])) {
            $query->forOwnership($filters['ownership_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['role'])) {
            $query->byRole($filters['role']);
        }

        if (isset($filters['active'])) {
            if ($filters['active']) {
                $query->active();
            } else {
                $query->where('active', false);
            }
        }

        return $query->with(['ownership', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Find board member by ID.
     */
    public function find(int $id): ?OwnershipBoardMember
    {
        return OwnershipBoardMember::with(['ownership', 'user'])->find($id);
    }

    /**
     * Find board member by ownership and user.
     */
    public function findByOwnershipAndUser(int $ownershipId, int $userId): ?OwnershipBoardMember
    {
        return OwnershipBoardMember::where('ownership_id', $ownershipId)
            ->where('user_id', $userId)
            ->with(['ownership', 'user'])
            ->first();
    }

    /**
     * Create a new board member.
     */
    public function create(array $data): OwnershipBoardMember
    {
        return OwnershipBoardMember::create($data);
    }

    /**
     * Update board member.
     */
    public function update(OwnershipBoardMember $boardMember, array $data): OwnershipBoardMember
    {
        $boardMember->update($data);
        return $boardMember->fresh(['ownership', 'user']);
    }

    /**
     * Delete board member.
     */
    public function delete(OwnershipBoardMember $boardMember): bool
    {
        return $boardMember->delete();
    }

    /**
     * Activate board member.
     */
    public function activate(OwnershipBoardMember $boardMember): OwnershipBoardMember
    {
        $boardMember->activate();
        return $boardMember->fresh(['ownership', 'user']);
    }

    /**
     * Deactivate board member.
     */
    public function deactivate(OwnershipBoardMember $boardMember): OwnershipBoardMember
    {
        $boardMember->deactivate();
        return $boardMember->fresh(['ownership', 'user']);
    }
}

