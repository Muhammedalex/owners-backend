<?php

use App\Http\Controllers\Api\V1\Invoice\InvoiceController;
use App\Http\Controllers\Api\V1\Invoice\InvoiceItemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Invoices API Routes - V1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/invoices
| All routes use UUID in route parameters
|
*/

Route::prefix('invoices')->name('v1.invoices.')->middleware(['auth:sanctum', 'ownership.scope'])->group(function () {
    // Invoice Items (MUST come before {invoice:uuid} routes to avoid route conflict)
    Route::prefix('{invoice:uuid}/items')->name('items.')->group(function () {
        Route::get('/', [InvoiceItemController::class, 'index'])->name('index');
        Route::post('/', [InvoiceItemController::class, 'store'])->name('store');
        Route::get('/{item}', [InvoiceItemController::class, 'show'])->name('show');
        Route::put('/{item}', [InvoiceItemController::class, 'update'])->name('update');
        Route::patch('/{item}', [InvoiceItemController::class, 'update'])->name('update.patch');
        Route::delete('/{item}', [InvoiceItemController::class, 'destroy'])->name('destroy');
    });
    
    // Invoice CRUD
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::get('/{invoice:uuid}', [InvoiceController::class, 'show'])->name('show');
    Route::put('/{invoice:uuid}', [InvoiceController::class, 'update'])->name('update');
    Route::patch('/{invoice:uuid}', [InvoiceController::class, 'update'])->name('update.patch');
    Route::delete('/{invoice:uuid}', [InvoiceController::class, 'destroy'])->name('destroy');
    Route::post('/{invoice:uuid}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('mark-as-paid');
    Route::post('/{invoice:uuid}/mark-as-sent', [InvoiceController::class, 'markAsSent'])->name('mark-as-sent');
});

