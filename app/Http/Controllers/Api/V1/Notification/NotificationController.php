<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Notification\StoreNotificationRequest;
use App\Http\Requests\V1\Notification\UpdateNotificationRequest;
use App\Http\Resources\V1\Notification\NotificationResource;
use App\Models\V1\Notification\Notification;
use App\Services\V1\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);
        $filters = $request->only(['read', 'type', 'category', 'priority', 'search', 'include_expired']);

        $notifications = $this->notificationService->paginateForUser($user->id, $perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications->items()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $this->notificationService->getUnreadCount($user->id),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $this->authorize('create', Notification::class);

        $notification = $this->notificationService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully.',
            'data' => new NotificationResource($notification),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        $notification = $this->notificationService->find($notification->id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNotificationRequest $request, Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification = $this->notificationService->update($notification, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Notification updated successfully.',
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $this->notificationService->delete($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.',
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification = $this->notificationService->markAsRead($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification = $this->notificationService->markAsUnread($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as unread.',
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->markAllAsRead($user->id);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read.",
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Delete all read notifications.
     */
    public function deleteAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->deleteAllRead($user->id);

        return response()->json([
            'success' => true,
            'message' => "Deleted {$count} read notifications.",
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Get unread count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->getUnreadCount($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    /**
     * Get latest notifications.
     */
    public function latest(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->input('limit', 10);
        $notifications = $this->notificationService->getLatestForUser($user->id, $limit);

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications),
        ]);
    }
}

