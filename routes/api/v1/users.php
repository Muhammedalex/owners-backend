<?php

use App\Http\Controllers\Api\V1\Auth\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Users API Routes - V1
|--------------------------------------------------------------------------
|
| Here are the user management routes for the API V1.
| All routes are prefixed with /api/v1/users
|
*/

Route::prefix('users')->name('v1.users.')->middleware('auth:sanctum')->group(function () {
    // User list - Super Admin can access without ownership scope
    // Non-Super Admin users will need ownership scope (handled in controller)
    Route::get('/', [UserController::class, 'index'])->name('index');
    
    // User creation - Super Admin can create users without ownership
    // Non-Super Admin users will be auto-linked to ownership (handled in controller)
    Route::post('/', [UserController::class, 'store'])->name('store');
    
    // User import routes - require ownership scope for non-Super Admin
    Route::get('/from-ownership', [UserController::class, 'getUsersFromOwnership'])->middleware('ownership.scope')->name('from-ownership');
    Route::post('/import', [UserController::class, 'import'])->middleware('ownership.scope')->name('import');
    
    // User CRUD routes - Super Admin can access without ownership scope
    Route::get('/{user}', [UserController::class, 'show'])->name('show');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::patch('/{user}', [UserController::class, 'update']); // Same as PUT, no separate name needed
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    
    // User status management - Super Admin can access without ownership scope
    Route::post('/{user}/activate', [UserController::class, 'activate'])->name('activate');
    Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
    
    // Role management - Super Admin can access without ownership scope
    Route::post('/{user}/roles/sync', [UserController::class, 'syncRoles'])->name('roles.sync');
    Route::post('/{user}/roles/assign', [UserController::class, 'assignRole'])->name('roles.assign');
    Route::post('/{user}/roles/remove', [UserController::class, 'removeRole'])->name('roles.remove');
});

