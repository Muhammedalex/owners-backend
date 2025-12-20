<?php

use App\Http\Controllers\Api\V1\Media\MediaFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Media Files API Routes - V1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/media
| All routes require authentication
| Ownership scope middleware applied where needed
|
*/

Route::prefix('media')->name('v1.media.')->group(function () {
    // Download media file (public files accessible without auth)
    Route::get('/{id}/download', [MediaFileController::class, 'download'])
    ->middleware('ownership.scope')
        ->name('download');
    
    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        // Upload media file
        Route::post('/upload', [MediaFileController::class, 'upload'])
            ->middleware('ownership.scope')
            ->name('upload');
        
        // List media files
        Route::get('/', [MediaFileController::class, 'index'])
            ->middleware('ownership.scope')
            ->name('index');
        
        // Reorder media files
        Route::post('/reorder', [MediaFileController::class, 'reorder'])
            ->middleware('ownership.scope')
            ->name('reorder');
        
        // CRUD operations
        Route::get('/{id}', [MediaFileController::class, 'show'])
            ->middleware('ownership.scope')
            ->name('show');
        
        Route::put('/{id}', [MediaFileController::class, 'update'])
            ->middleware('ownership.scope')
            ->name('update');
        
        Route::delete('/{id}', [MediaFileController::class, 'destroy'])
            ->middleware('ownership.scope')
            ->name('destroy');
    });
});

