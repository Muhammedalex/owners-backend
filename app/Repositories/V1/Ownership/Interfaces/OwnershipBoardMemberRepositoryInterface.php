<?php

namespace App\Repositories\V1\Ownership\Interfaces;

use App\Models\V1\Ownership\OwnershipBoardMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OwnershipBoardMemberRepositoryInterface
{
    /**
     * Get all board members with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all board members.
     */
    public function all(array $filters = []): Collection;

    /**
     * Find board member by ID.
     */
    public function find(int $id): ?OwnershipBoardMember;

    /**
     * Find board member by ownership and user.
     */
    public function findByOwnershipAndUser(int $ownershipId, int $userId): ?OwnershipBoardMember;

    /**
     * Create a new board member.
     */
    public function create(array $data): OwnershipBoardMember;

    /**
     * Update board member.
     */
    public function update(OwnershipBoardMember $boardMember, array $data): OwnershipBoardMember;

    /**
     * Delete board member.
     */
    public function delete(OwnershipBoardMember $boardMember): bool;

    /**
     * Activate board member.
     */
    public function activate(OwnershipBoardMember $boardMember): OwnershipBoardMember;

    /**
     * Deactivate board member.
     */
    public function deactivate(OwnershipBoardMember $boardMember): OwnershipBoardMember;
}

