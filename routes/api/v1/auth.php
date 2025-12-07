<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth API Routes - V1
|--------------------------------------------------------------------------
|
| Here are the authentication routes for the API V1.
| All routes are prefixed with /api/v1/auth
|
*/

Route::prefix('auth')->name('v1.auth.')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verify-email');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        // Me endpoint needs ownership scope to return current_ownership_uuid
        Route::get('/me', [AuthController::class, 'me'])->middleware('ownership.scope')->name('me');
        Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->name('resend-verification');
    });
});

