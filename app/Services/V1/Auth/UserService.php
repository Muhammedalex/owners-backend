<?php

namespace App\Services\V1\Auth;

use App\Models\V1\Auth\User;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use App\Services\V1\Document\DocumentService;
use App\Services\V1\Media\MediaService;
use App\Services\V1\Notification\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private NotificationService $notificationService,
        private MediaService $mediaService,
        private DocumentService $documentService
    ) {}

    /**
     * Get all users with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage, $filters);
    }

    /**
     * Get all users.
     */
    public function all(array $filters = []): Collection
    {
        return $this->userRepository->all($filters);
    }

    /**
     * Find user by ID.
     */
    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Find user by UUID.
     */
    public function findByUuid(string $uuid): ?User
    {
        return $this->userRepository->findByUuid($uuid);
    }

    /**
     * Create a new user.
     * 
     * @param array $data User data
     * @param User|null $currentUser The user creating this user (null for public registration)
     * @param int|null $ownershipId Ownership ID to auto-link (from cookie scope, only for non-Super Admin)
     * @return User
     */
    public function create(array $data, ?User $currentUser = null, ?int $ownershipId = null): User
    {
        $user = $this->userRepository->create($data);

        // Auto-assign role based on user type if roles are not explicitly provided
        if (!isset($data['roles']) || !is_array($data['roles']) || empty($data['roles'])) {
            $roleName = $this->getRoleNameByUserType($data['type'] ?? null);
            if ($roleName) {
                $this->assignRole($user, $roleName);
            }
        } else {
            // Sync roles if provided explicitly
            $this->syncRoles($user, $data['roles']);
        }

        // If current user is not Super Admin and ownership_id is provided, auto-link user to ownership
        if ($currentUser && !$currentUser->isSuperAdmin() && $ownershipId) {
            $this->linkUserToOwnership($user->id, $ownershipId, $data['is_default'] ?? false);
        }

        // Notify all Super Admins about the new user
        $this->notifySuperAdminsOfNewUser($user);

        return $user->load('roles');
    }

    /**
     * Link user to ownership.
     */
    protected function linkUserToOwnership(int $userId, int $ownershipId, bool $isDefault = false): void
    {
        $mappingService = app(\App\Services\V1\Ownership\UserOwnershipMappingService::class);
        
        try {
            $mappingService->create([
                'user_id' => $userId,
                'ownership_id' => $ownershipId,
                'default' => $isDefault,
            ]);
        } catch (\Exception $e) {
            // If mapping already exists, ignore the error
            // This can happen if user is already linked to this ownership
        }
    }

    /**
     * Notify all Super Admin users about a new user creation.
     */
    protected function notifySuperAdminsOfNewUser(User $newUser): void
    {
        try {
            // Get all users with Super Admin role (bypass scope to ensure we can find Super Admins)
            $superAdmins = User::withSuperAdmins()->role('Super Admin')->get();

            if ($superAdmins->isEmpty()) {
                return;
            }

            // Prepare notification data
            $userName = trim(($newUser->first ?? '') . ' ' . ($newUser->last ?? ''));
            $userName = !empty($userName) ? $userName : $newUser->email;
            
            $notificationData = [
                'type' => 'info',
                'title' => 'New User Registered',
                'message' => "A new user has been registered: {$userName} ({$newUser->email})",
                'category' => 'users',
                'priority' => 1, // High priority
                'icon' => 'user-plus',
                'data' => [
                    'user_id' => $newUser->id,
                    'user_uuid' => $newUser->uuid,
                    'user_email' => $newUser->email,
                    'user_name' => $userName,
                    'user_type' => $newUser->type,
                ],
                'action_url' => "/users/{$newUser->uuid}",
                'action_text' => 'View User',
            ];

            // Send notification to each Super Admin
            foreach ($superAdmins as $superAdmin) {
                // Skip if the new user is a Super Admin themselves
                if ($superAdmin->id === $newUser->id) {
                    continue;
                }

                try {
                    $this->notificationService->create([
                        ...$notificationData,
                        'user_id' => $superAdmin->id,
                    ]);
                } catch (\Throwable $e) {
                    // Ignore any notification errors - don't let notification failures break user creation
                    // Log error in development/testing environments only
                    if (app()->environment(['local', 'testing'])) {
                        \Illuminate\Support\Facades\Log::warning('Failed to send notification to Super Admin', [
                            'super_admin_id' => $superAdmin->id,
                            'new_user_id' => $newUser->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore any errors in notification process - don't let notification failures break user creation
            // Log error in development/testing environments only
            if (app()->environment(['local', 'testing'])) {
                \Illuminate\Support\Facades\Log::warning('Failed to notify Super Admins of new user', [
                    'new_user_id' => $newUser->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Update user.
     */
    public function update(User $user, array $data): User
    {
        $oldType = $user->type;
        $user = $this->userRepository->update($user, $data);

        // Sync roles if provided explicitly
        if (isset($data['roles']) && is_array($data['roles'])) {
            $this->syncRoles($user, $data['roles']);
        } elseif (isset($data['type']) && $data['type'] !== $oldType) {
            // If type changed and roles not explicitly provided, update role based on new type
            $roleName = $this->getRoleNameByUserType($data['type']);
            if ($roleName) {
                // Remove old role and assign new role
                $user->syncRoles([]); // Clear existing roles
                $this->assignRole($user, $roleName);
            }
        }

        return $user->load('roles');
    }

    /**
     * Delete user.
     */
    public function delete(User $user): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
            // Load relationships
            $user->load(['mediaFiles', 'documents']);

            // Delete all media files
            foreach ($user->mediaFiles as $mediaFile) {
                $this->mediaService->delete($mediaFile);
            }

            // Delete all documents
            foreach ($user->documents as $document) {
                $this->documentService->delete($document);
            }

            return $this->userRepository->delete($user);
        });
    }

    /**
     * Activate user.
     */
    public function activate(User $user): User
    {
        return $this->userRepository->activate($user);
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): User
    {
        return $this->userRepository->deactivate($user);
    }

    /**
     * Sync roles to user.
     */
    public function syncRoles(User $user, array $roleNames): User
    {
        $roles = \App\Models\V1\Auth\Role::whereIn('name', $roleNames)->get();
        $user->syncRoles($roles);
        return $user->load('roles');
    }

    /**
     * Give role to user.
     */
    public function assignRole(User $user, string $roleName): User
    {
        $role = \App\Models\V1\Auth\Role::where('name', $roleName)->first();
        if ($role) {
            $user->assignRole($role);
        }
        return $user->load('roles');
    }

    /**
     * Revoke role from user.
     */
    public function removeRole(User $user, string $roleName): User
    {
        $role = \App\Models\V1\Auth\Role::where('name', $roleName)->first();
        if ($role) {
            $user->removeRole($role);
        }
        return $user->load('roles');
    }

    /**
     * Get role name based on user type.
     * Maps user types to their corresponding roles.
     * 
     * @param string|null $userType
     * @return string|null Role name or null if no mapping exists
     */
    protected function getRoleNameByUserType(?string $userType): ?string
    {
        if (!$userType) {
            return null;
        }

        // Map user types to role names
        $typeToRoleMap = [
            'super_admin' => 'Super Admin',
            'owner' => 'Owner',
            'tenant' => 'Tenant',
            'accountant' => 'Accountant',
            'moderator' => 'Moderator',
            'board_member' => 'Board Member',
            'property_manager' => 'Property Manager',
            'maintenance_manager' => 'Maintenance Manager',
            'facility_manager' => 'Facility Manager',
            'collector' => 'Collector',
        ];

        return $typeToRoleMap[$userType] ?? null;
    }

    /**
     * Get users by ownership ID.
     *
     * @param int $ownershipId
     * @param array $excludeUserIds User IDs to exclude
     * @return Collection
     */
    public function getUsersByOwnership(int $ownershipId, array $excludeUserIds = []): Collection
    {
        return $this->userRepository->getUsersByOwnership($ownershipId, $excludeUserIds);
    }
}

