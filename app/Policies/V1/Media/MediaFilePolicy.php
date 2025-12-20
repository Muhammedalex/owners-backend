<?php

namespace App\Policies\V1\Media;

use App\Models\V1\Auth\User;
use App\Models\V1\Media\MediaFile;

class MediaFilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin can view all media files
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('media.view') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MediaFile $mediaFile): bool
    {
        if (!$user->can('media.view')) {
            return false;
        }

        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check ownership access
        return $user->hasOwnership($mediaFile->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Super Admin can create media files
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('media.create') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MediaFile $mediaFile): bool
    {
        if (!$user->can('media.update')) {
            return false;
        }

        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check ownership access
        return $user->hasOwnership($mediaFile->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MediaFile $mediaFile): bool
    {
        if (!$user->can('media.delete')) {
            return false;
        }

        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check ownership access
        return $user->hasOwnership($mediaFile->ownership_id);
    }

    /**
     * Determine whether the user can download the model.
     */
    public function download(User $user, MediaFile $mediaFile): bool
    {
        if (!$user->can('media.download')) {
            return false;
        }

        // Super Admin can download all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check ownership access
        return $user->hasOwnership($mediaFile->ownership_id);
    }
}

