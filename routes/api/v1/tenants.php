<?php

use App\Http\Controllers\Api\V1\Tenant\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenants API Routes - V1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/tenants
| All routes use ID in route parameters (Tenant model doesn't have UUID)
|
*/

Route::prefix('tenants')->name('v1.tenants.')->middleware(['auth:sanctum', 'ownership.scope'])->group(function () {
    Route::get('/', [TenantController::class, 'index'])->name('index');
    Route::post('/', [TenantController::class, 'store'])->name('store');
    
    // Tenant Invitations (MUST come before {tenant} routes to avoid route conflict)
    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Tenant\TenantInvitationController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\V1\Tenant\TenantInvitationController::class, 'store'])->name('store');
        Route::post('/bulk', [\App\Http\Controllers\Api\V1\Tenant\TenantInvitationController::class, 'storeBulk'])->name('store.bulk');
        Route::post('/generate-link', [\App\Http\Controllers\Api\V1\Tenant\TenantInvitationController::class, 'generateLink'])->name('generate-link');
        Route::get('/{invitation:uuid}', [\App\Http\Controllers\Api\V1\Tenant\TenantInvitationController::class, 'show'])->name('show');
        Route::post('/{invitation:uuid}/resend', [\App\Http\Controllers\Api\V1\Tenant\TenantInvitationController::class, 'resend'])->name('resend');
        Route::post('/{invitation:uuid}/cancel', [\App\Http\Controllers\Api\V1\Tenant\TenantInvitationController::class, 'cancel'])->name('cancel');
    });
    
    // Tenant CRUD routes (must come AFTER invitations to avoid conflict)
    Route::get('/{tenant}', [TenantController::class, 'show'])->name('show');
    Route::post('/{tenant}', [TenantController::class, 'update'])->name('update');
    Route::patch('/{tenant}', [TenantController::class, 'update'])->name('update.patch');
    Route::delete('/{tenant}', [TenantController::class, 'destroy'])->name('destroy');
});

// Public Tenant Invitation Routes (no authentication required)
Route::prefix('public/tenant-invitations')->name('v1.public.tenant-invitations.')->group(function () {
    Route::get('/{token}/validate', [\App\Http\Controllers\Api\V1\Tenant\PublicTenantInvitationController::class, 'validateToken'])->name('validate');
    Route::post('/{token}/accept', [\App\Http\Controllers\Api\V1\Tenant\PublicTenantInvitationController::class, 'accept'])->name('accept');
});

// Test Routes (Development Only - No Authentication)
if (!app()->environment('production')) {
    Route::prefix('test/tenant-invitations')->name('v1.test.tenant-invitations.')->group(function () {
        Route::post('/create', [\App\Http\Controllers\Api\V1\Tenant\TestTenantInvitationController::class, 'testCreate'])->name('create');
        Route::post('/bulk', [\App\Http\Controllers\Api\V1\Tenant\TestTenantInvitationController::class, 'testBulk'])->name('bulk');
        Route::post('/generate-link', [\App\Http\Controllers\Api\V1\Tenant\TestTenantInvitationController::class, 'testGenerateLink'])->name('generate-link');
    });
}

