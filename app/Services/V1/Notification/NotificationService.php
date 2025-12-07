<?php

namespace App\Services\V1\Notification;

use App\Events\V1\Notification\NotificationCreated;
use App\Models\V1\Notification\Notification;
use App\Repositories\V1\Notification\Interfaces\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    /**
     * Get all notifications for a user with pagination.
     */
    public function paginateForUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->notificationRepository->paginateForUser($userId, $perPage, $filters);
    }

    /**
     * Get all notifications for a user.
     */
    public function allForUser(int $userId, array $filters = []): Collection
    {
        return $this->notificationRepository->allForUser($userId, $filters);
    }

    /**
     * Find notification by ID.
     */
    public function find(int $id): ?Notification
    {
        return $this->notificationRepository->find($id);
    }

    /**
     * Find notification by UUID.
     */
    public function findByUuid(string $uuid): ?Notification
    {
        return $this->notificationRepository->findByUuid($uuid);
    }

    /**
     * Create a new notification and broadcast it.
     */
    public function create(array $data): Notification
    {
        $notification = $this->notificationRepository->create($data);

        // Broadcast notification in real-time
        event(new NotificationCreated($notification));

        return $notification;
    }

    /**
     * Create multiple notifications.
     */
    public function createMany(array $notifications): Collection
    {
        $created = collect();

        foreach ($notifications as $data) {
            $notification = $this->create($data);
            $created->push($notification);
        }

        return $created;
    }

    /**
     * Update notification.
     */
    public function update(Notification $notification, array $data): Notification
    {
        return $this->notificationRepository->update($notification, $data);
    }

    /**
     * Delete notification.
     */
    public function delete(Notification $notification): bool
    {
        return $this->notificationRepository->delete($notification);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): Notification
    {
        return $this->notificationRepository->markAsRead($notification);
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(Notification $notification): Notification
    {
        return $this->notificationRepository->markAsUnread($notification);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->notificationRepository->markAllAsRead($userId);
    }

    /**
     * Delete all read notifications for a user.
     */
    public function deleteAllRead(int $userId): int
    {
        return $this->notificationRepository->deleteAllRead($userId);
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->getUnreadCount($userId);
    }

    /**
     * Get latest notifications for a user.
     */
    public function getLatestForUser(int $userId, int $limit = 10): Collection
    {
        return $this->notificationRepository->getLatestForUser($userId, $limit);
    }
}

