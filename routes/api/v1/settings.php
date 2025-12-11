<?php

use App\Http\Controllers\Api\V1\Setting\SystemSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings API Routes - V1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/settings
| All routes require authentication
| Ownership scope middleware applied where needed
|
*/

Route::prefix('settings')->name('v1.settings.')->middleware(['auth:sanctum'])->group(function () {
    // List all settings (with scope filter)
    // Note: ownership.scope middleware is optional here because scope can be 'system' (Super Admin only)
    // If scope=ownership, the middleware will set current_ownership_id
    Route::get('/', [SystemSettingController::class, 'index'])
        ->middleware('ownership.scope')
        ->name('index');
    
    // Get all settings for current ownership (with system defaults)
    Route::get('/all', [SystemSettingController::class, 'getAll'])
        ->middleware('ownership.scope')
        ->name('all');
    
    // Get settings by group
    Route::get('/group/{group}', [SystemSettingController::class, 'getByGroup'])
        ->middleware('ownership.scope')
        ->name('group');
    
    // Get setting by key
    Route::get('/key/{key}', [SystemSettingController::class, 'getByKey'])
        ->middleware('ownership.scope')
        ->name('key');
    
    // Bulk update settings
    Route::put('/bulk', [SystemSettingController::class, 'bulkUpdate'])
        ->middleware('ownership.scope')
        ->name('bulk');
    
    // CRUD operations
    Route::post('/', [SystemSettingController::class, 'store'])
        ->middleware('ownership.scope')
        ->name('store');
    
    Route::get('/{setting}', [SystemSettingController::class, 'show'])
        ->middleware('ownership.scope')
        ->name('show');
    
    Route::put('/{setting}', [SystemSettingController::class, 'update'])
        ->middleware('ownership.scope')
        ->name('update');
    
    Route::delete('/{setting}', [SystemSettingController::class, 'destroy'])
        ->middleware('ownership.scope')
        ->name('destroy');
});

