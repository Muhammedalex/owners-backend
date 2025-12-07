<?php

namespace App\Repositories\V1\Notification\Interfaces;

use App\Models\V1\Notification\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /**
     * Get all notifications for a user with pagination.
     */
    public function paginateForUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get all notifications for a user.
     */
    public function allForUser(int $userId, array $filters = []): Collection;

    /**
     * Find notification by ID.
     */
    public function find(int $id): ?Notification;

    /**
     * Find notification by UUID.
     */
    public function findByUuid(string $uuid): ?Notification;

    /**
     * Create a new notification.
     */
    public function create(array $data): Notification;

    /**
     * Update notification.
     */
    public function update(Notification $notification, array $data): Notification;

    /**
     * Delete notification.
     */
    public function delete(Notification $notification): bool;

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): Notification;

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(Notification $notification): Notification;

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int;

    /**
     * Delete all read notifications for a user.
     */
    public function deleteAllRead(int $userId): int;

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int;

    /**
     * Get latest notifications for a user.
     */
    public function getLatestForUser(int $userId, int $limit = 10): Collection;
}

