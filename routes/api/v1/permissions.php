<?php

use App\Http\Controllers\Api\V1\Auth\PermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Permissions API Routes - V1 (Read Only)
|--------------------------------------------------------------------------
|
| Permissions are hard-coded in seeders and cannot be created/updated/deleted via API.
| Only viewing and grouping operations are allowed.
| All routes are prefixed with /api/v1/permissions
|
*/

Route::prefix('permissions')->name('v1.permissions.')->middleware('auth:sanctum')->group(function () {
    // Read-only routes
    Route::get('/', [PermissionController::class, 'index'])->name('index');
    Route::get('/grouped', [PermissionController::class, 'groupedByModule'])->name('grouped');
    Route::get('/{permission}', [PermissionController::class, 'show'])->name('show');
    
    // Note: POST, PUT, PATCH, DELETE routes removed
    // Permissions can only be managed through seeders
});

