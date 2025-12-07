<?php

namespace App\Policies\V1\Auth;

use App\Models\V1\Auth\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // User can view if they have either 'auth.users.view' or 'auth.users.view.own'
        return $user->can('auth.users.view') || $user->can('auth.users.view.own');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // If user has full view permission, allow
        if ($user->can('auth.users.view')) {
            return true;
        }

        // If user has view.own permission, check if both users are in the same ownership
        if ($user->can('auth.users.view.own')) {
            // Get ownership IDs for both users
            $userOwnershipIds = $user->getOwnershipIds();
            $modelOwnershipIds = $model->getOwnershipIds();

            // Check if they share at least one ownership
            return !empty(array_intersect($userOwnershipIds, $modelOwnershipIds));
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('auth.users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can always update their own profile
        if ($user->id === $model->id) {
            return $user->can('auth.users.update.own');
        }

        return $user->can('auth.users.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('auth.users.delete');
    }

    /**
     * Determine whether the user can activate the model.
     */
    public function activate(User $user, User $model): bool
    {
        return $user->can('auth.users.activate');
    }

    /**
     * Determine whether the user can deactivate the model.
     */
    public function deactivate(User $user, User $model): bool
    {
        // Users cannot deactivate themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can('auth.users.deactivate');
    }
}

