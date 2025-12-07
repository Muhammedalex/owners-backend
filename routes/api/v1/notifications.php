<?php

use App\Http\Controllers\Api\V1\Notification\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notifications API Routes - V1
|--------------------------------------------------------------------------
|
| Here are the notification management routes for the API V1.
| All routes are prefixed with /api/v1/notifications
|
*/

Route::prefix('notifications')->name('v1.notifications.')->middleware('auth:sanctum')->group(function () {
    // User's own notifications
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/latest', [NotificationController::class, 'latest'])->name('latest');
    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::delete('/delete-all-read', [NotificationController::class, 'deleteAllRead'])->name('delete-all-read');
    
    // Single notification operations
    Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
    Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
    Route::patch('/{notification}', [NotificationController::class, 'update']); // Same as PUT, no separate name needed
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
    Route::post('/{notification}/unread', [NotificationController::class, 'markAsUnread'])->name('mark-unread');
    
    // System endpoint (requires permission)
    Route::post('/', [NotificationController::class, 'store'])->name('store')->middleware('can:notifications.create');
});

