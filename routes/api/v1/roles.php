<?php

use App\Http\Controllers\Api\V1\Auth\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Roles API Routes - V1
|--------------------------------------------------------------------------
|
| Here are the role management routes for the API V1.
| All routes are prefixed with /api/v1/roles
|
*/

Route::prefix('roles')->name('v1.roles.')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('/{role}', [RoleController::class, 'show'])->name('show');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::patch('/{role}', [RoleController::class, 'update']); // Same as PUT, no separate name needed
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    
    // Permission management
    Route::post('/{role}/permissions/sync', [RoleController::class, 'syncPermissions'])->name('permissions.sync');
    Route::post('/{role}/permissions/give', [RoleController::class, 'givePermission'])->name('permissions.give');
    Route::post('/{role}/permissions/revoke', [RoleController::class, 'revokePermission'])->name('permissions.revoke');
});

