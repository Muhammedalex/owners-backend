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
    Route::get('/{tenant}', [TenantController::class, 'show'])->name('show');
    Route::put('/{tenant}', [TenantController::class, 'update'])->name('update');
    Route::patch('/{tenant}', [TenantController::class, 'update'])->name('update.patch');
    Route::delete('/{tenant}', [TenantController::class, 'destroy'])->name('destroy');
});

