<?php

namespace App\Services\V1\Auth;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Services\V1\Ownership\UserOwnershipMappingService;
use App\Services\V1\Tenant\TenantService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserImportService
{
    public function __construct(
        private UserOwnershipMappingService $mappingService,
        private TenantService $tenantService
    ) {}

    /**
     * Import users from source ownership to target ownership.
     *
     * @param int $sourceOwnershipId Source ownership ID
     * @param array $userIds Array of user IDs to import
     * @param int $targetOwnershipId Target ownership ID (current ownership)
     * @param User $currentUser Current user performing the import
     * @param bool $createTenantIfNeeded Whether to create tenant records if user is tenant
     * @return array Import results
     */
    public function importUsers(
        int $sourceOwnershipId,
        array $userIds,
        int $targetOwnershipId,
        User $currentUser,
        bool $createTenantIfNeeded = true
    ): array {
        return DB::transaction(function () use (
            $sourceOwnershipId,
            $userIds,
            $targetOwnershipId,
            $currentUser,
            $createTenantIfNeeded
        ) {
            // Validate ownerships exist
            $sourceOwnership = Ownership::findOrFail($sourceOwnershipId);
            $targetOwnership = Ownership::findOrFail($targetOwnershipId);

            // Validate user has access to both ownerships
            $this->validateOwnershipAccess($currentUser, $sourceOwnershipId, $targetOwnershipId);

            // Get users from source ownership
            $users = $this->getUsersFromOwnership($sourceOwnershipId, $userIds);

            // Filter out users already mapped to target ownership
            $usersToImport = $this->filterUsersNotInOwnership($users, $targetOwnershipId);

            $imported = [];
            $skipped = [];
            $tenantsCreated = 0;

            foreach ($usersToImport as $user) {
                try {
                    // Create user ownership mapping
                    $mapping = $this->mappingService->create([
                        'user_id' => $user->id,
                        'ownership_id' => $targetOwnershipId,
                        'default' => false, // Don't set as default when importing
                    ]);

                    $imported[] = $user;

                    // Create tenant record if user is tenant and flag is enabled
                    if ($createTenantIfNeeded && $this->isTenant($user)) {
                        $tenantCreated = $this->createTenantIfNotExists($user->id, $targetOwnershipId);
                        if ($tenantCreated) {
                            $tenantsCreated++;
                        }
                    }
                } catch (\Exception $e) {
                    // If mapping already exists or other error, skip
                    Log::warning("Failed to import user {$user->id}: {$e->getMessage()}");
                    $skipped[] = [
                        'user' => $user,
                        'reason' => $e->getMessage(),
                    ];
                }
            }

            // Add users that were already mapped to skipped list
            $alreadyMappedUsers = $users->diff($usersToImport);
            foreach ($alreadyMappedUsers as $user) {
                $skipped[] = [
                    'user' => $user,
                    'reason' => 'User is already mapped to this ownership',
                ];
            }

            return [
                'imported' => count($imported),
                'skipped' => count($skipped),
                'tenants_created' => $tenantsCreated,
                'imported_users' => $imported,
                'skipped_users' => $skipped,
            ];
        });
    }

    /**
     * Validate that current user has access to both ownerships.
     */
    protected function validateOwnershipAccess(User $user, int $sourceOwnershipId, int $targetOwnershipId): void
    {
        // Super Admin has access to all ownerships
        if ($user->isSuperAdmin()) {
            return;
        }

        $userOwnershipIds = $user->getOwnershipIds();

        // Check source ownership access
        if (!in_array($sourceOwnershipId, $userOwnershipIds)) {
            throw new \Exception('You do not have access to the source ownership.');
        }

        // Check target ownership access
        if (!in_array($targetOwnershipId, $userOwnershipIds)) {
            throw new \Exception('You do not have access to the target ownership.');
        }

        // Source and target cannot be the same
        if ($sourceOwnershipId === $targetOwnershipId) {
            throw new \Exception('Source and target ownership cannot be the same.');
        }
    }

    /**
     * Get users from source ownership.
     */
    protected function getUsersFromOwnership(int $ownershipId, array $userIds): Collection
    {
        return User::whereIn('id', $userIds)
            ->whereHas('ownershipMappings', function ($query) use ($ownershipId) {
                $query->where('ownership_id', $ownershipId);
            })
            ->with('roles')
            ->get();
    }

    /**
     * Filter out users already mapped to target ownership.
     */
    protected function filterUsersNotInOwnership(Collection $users, int $ownershipId): Collection
    {
        return $users->filter(function ($user) use ($ownershipId) {
            return !$user->hasOwnership($ownershipId);
        });
    }

    /**
     * Check if user is a tenant.
     */
    protected function isTenant(User $user): bool
    {
        // Check if user has Tenant role
        if ($user->hasRole('Tenant')) {
            return true;
        }

        // Check if user type is tenant
        if ($user->type === 'tenant') {
            return true;
        }

        return false;
    }

    /**
     * Create tenant record if it doesn't exist for this user and ownership.
     */
    protected function createTenantIfNotExists(int $userId, int $ownershipId): bool
    {
        // Check if tenant already exists for this user and ownership
        $existingTenant = $this->tenantService->findByUserAndOwnership($userId, $ownershipId);

        if ($existingTenant) {
            return false; // Already exists
        }

        try {
            // Create tenant record with minimal data
            // Most fields will be null and can be filled later
            $this->tenantService->create([
                'user_id' => $userId,
                'ownership_id' => $ownershipId,
                'rating' => 'good', // Default rating
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create tenant record for user {$userId} in ownership {$ownershipId}: {$e->getMessage()}");
            return false;
        }
    }
}

