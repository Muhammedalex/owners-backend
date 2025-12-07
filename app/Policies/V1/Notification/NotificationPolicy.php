<?php

namespace App\Policies\V1\Notification;

use App\Models\V1\Auth\User;
use App\Models\V1\Notification\Notification;

class NotificationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can always view their own notifications
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Notification $notification): bool
    {
        // Users can only view their own notifications
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only system can create notifications via API
        // Regular users cannot create notifications for themselves
        return $user->can('notifications.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Notification $notification): bool
    {
        // Users can only update their own notifications
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Notification $notification): bool
    {
        // Users can only delete their own notifications
        return $user->id === $notification->user_id;
    }
}

