<?php

use App\Http\Controllers\Api\V1\Contract\ContractController;
use App\Http\Controllers\Api\V1\Contract\ContractTermController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Contracts API Routes - V1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/contracts
| All routes use UUID in route parameters
|
*/

Route::prefix('contracts')->name('v1.contracts.')->middleware(['auth:sanctum', 'ownership.scope'])->group(function () {
    // Contract Terms (MUST come before {contract:uuid} routes to avoid route conflict)
    Route::prefix('{contract:uuid}/terms')->name('terms.')->group(function () {
        Route::get('/', [ContractTermController::class, 'index'])->name('index');
        Route::post('/', [ContractTermController::class, 'store'])->name('store');
        Route::get('/{term}', [ContractTermController::class, 'show'])->name('show');
        Route::put('/{term}', [ContractTermController::class, 'update'])->name('update');
        Route::patch('/{term}', [ContractTermController::class, 'update'])->name('update.patch');
        Route::delete('/{term}', [ContractTermController::class, 'destroy'])->name('destroy');
    });
    
    // Contract CRUD
    Route::get('/', [ContractController::class, 'index'])->name('index');
    Route::post('/', [ContractController::class, 'store'])->name('store');
    Route::get('/{contract:uuid}', [ContractController::class, 'show'])->name('show');
    Route::post('/{contract:uuid}', [ContractController::class, 'update'])->name('update');
    Route::patch('/{contract:uuid}', [ContractController::class, 'update'])->name('update.patch');
    Route::delete('/{contract:uuid}', [ContractController::class, 'destroy'])->name('destroy');
    Route::post('/{contract:uuid}/approve', [ContractController::class, 'approve'])->name('approve');
    Route::post('/{contract:uuid}/cancel', [ContractController::class, 'cancel'])->name('cancel');
    Route::post('/{contract:uuid}/terminate', [ContractController::class, 'terminate'])->name('terminate');
});

