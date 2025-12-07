<?php

namespace App\Services\V1\Ownership;

use App\Models\V1\Ownership\OwnershipBoardMember;
use App\Repositories\V1\Ownership\Interfaces\OwnershipBoardMemberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OwnershipBoardMemberService
{
    public function __construct(
        private OwnershipBoardMemberRepositoryInterface $boardMemberRepository
    ) {}

    /**
     * Get all board members with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->boardMemberRepository->paginate($perPage, $filters);
    }

    /**
     * Get all board members.
     */
    public function all(array $filters = []): Collection
    {
        return $this->boardMemberRepository->all($filters);
    }

    /**
     * Find board member by ID.
     */
    public function find(int $id): ?OwnershipBoardMember
    {
        return $this->boardMemberRepository->find($id);
    }

    /**
     * Find board member by ownership and user.
     */
    public function findByOwnershipAndUser(int $ownershipId, int $userId): ?OwnershipBoardMember
    {
        return $this->boardMemberRepository->findByOwnershipAndUser($ownershipId, $userId);
    }

    /**
     * Create a new board member.
     */
    public function create(array $data): OwnershipBoardMember
    {
        return DB::transaction(function () use ($data) {
            // Check if board member already exists
            $existing = $this->boardMemberRepository->findByOwnershipAndUser(
                $data['ownership_id'],
                $data['user_id']
            );

            if ($existing) {
                throw new \Exception('Board member already exists for this ownership and user.');
            }

            return $this->boardMemberRepository->create($data);
        });
    }

    /**
     * Update board member.
     */
    public function update(OwnershipBoardMember $boardMember, array $data): OwnershipBoardMember
    {
        return DB::transaction(function () use ($boardMember, $data) {
            return $this->boardMemberRepository->update($boardMember, $data);
        });
    }

    /**
     * Delete board member.
     */
    public function delete(OwnershipBoardMember $boardMember): bool
    {
        return DB::transaction(function () use ($boardMember) {
            return $this->boardMemberRepository->delete($boardMember);
        });
    }

    /**
     * Activate board member.
     */
    public function activate(OwnershipBoardMember $boardMember): OwnershipBoardMember
    {
        return $this->boardMemberRepository->activate($boardMember);
    }

    /**
     * Deactivate board member.
     */
    public function deactivate(OwnershipBoardMember $boardMember): OwnershipBoardMember
    {
        return $this->boardMemberRepository->deactivate($boardMember);
    }
}

