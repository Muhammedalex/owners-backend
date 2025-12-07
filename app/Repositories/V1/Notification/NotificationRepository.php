<?php

namespace App\Repositories\V1\Notification;

use App\Models\V1\Notification\Notification;
use App\Repositories\V1\Notification\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Get all notifications for a user with pagination.
     */
    public function paginateForUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $userId);

        // Apply filters
        if (isset($filters['read'])) {
            $query->where('read', $filters['read']);
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['category'])) {
            $query->ofCategory($filters['category']);
        }

        if (isset($filters['priority'])) {
            $query->ofPriority($filters['priority']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Exclude expired notifications by default
        if (!isset($filters['include_expired']) || !$filters['include_expired']) {
            $query->notExpired();
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get all notifications for a user.
     */
    public function allForUser(int $userId, array $filters = []): Collection
    {
        $query = Notification::where('user_id', $userId);

        // Apply filters
        if (isset($filters['read'])) {
            $query->where('read', $filters['read']);
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['category'])) {
            $query->ofCategory($filters['category']);
        }

        if (!isset($filters['include_expired']) || !$filters['include_expired']) {
            $query->notExpired();
        }

        return $query->latest()->get();
    }

    /**
     * Find notification by ID.
     */
    public function find(int $id): ?Notification
    {
        return Notification::find($id);
    }

    /**
     * Find notification by UUID.
     */
    public function findByUuid(string $uuid): ?Notification
    {
        return Notification::where('uuid', $uuid)->first();
    }

    /**
     * Create a new notification.
     */
    public function create(array $data): Notification
    {
        // Ensure UUID is set if not provided
        if (!isset($data['uuid']) || empty($data['uuid'])) {
            $data['uuid'] = (string) \Illuminate\Support\Str::uuid();
        }

        return Notification::create($data);
    }

    /**
     * Update notification.
     */
    public function update(Notification $notification, array $data): Notification
    {
        $notification->update($data);
        return $notification->fresh();
    }

    /**
     * Delete notification.
     */
    public function delete(Notification $notification): bool
    {
        return $notification->delete();
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->markAsRead();
        return $notification->fresh();
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(Notification $notification): Notification
    {
        $notification->markAsUnread();
        return $notification->fresh();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Delete all read notifications for a user.
     */
    public function deleteAllRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('read', true)
            ->delete();
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('read', false)
            ->notExpired()
            ->count();
    }

    /**
     * Get latest notifications for a user.
     */
    public function getLatestForUser(int $userId, int $limit = 10): Collection
    {
        return Notification::where('user_id', $userId)
            ->notExpired()
            ->latest()
            ->limit($limit)
            ->get();
    }
}

