<?php

namespace App\Policies\V1\Document;

use App\Models\V1\Auth\User;
use App\Models\V1\Document\Document;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('documents.view') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        if (!$user->can('documents.view')) {
            return false;
        }

        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check ownership access
        return $user->hasOwnership($document->ownership_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('documents.create') && $user->ownershipMappings()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        if (!$user->can('documents.update')) {
            return false;
        }

        // Super Admin can update all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check ownership access
        return $user->hasOwnership($document->ownership_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        if (!$user->can('documents.delete')) {
            return false;
        }

        // Super Admin can delete all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check ownership access
        return $user->hasOwnership($document->ownership_id);
    }
}

