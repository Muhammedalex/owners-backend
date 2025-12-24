<?php

use App\Http\Controllers\Api\V1\Invoice\CollectorController;
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

// Collector routes (MUST come before invoices routes to avoid route conflict)
// Note: collectorId is a simple integer parameter, not a model binding
Route::prefix('collectors')->name('v1.collectors.')->middleware(['auth:sanctum', 'ownership.scope'])->group(function () {
    Route::get('/', [CollectorController::class, 'index'])->name('index');
    Route::get('/{collectorId}', [CollectorController::class, 'show'])->where('collectorId', '[0-9]+')->name('show');
    Route::post('/{collectorId}/assign-tenants', [CollectorController::class, 'assignTenants'])->where('collectorId', '[0-9]+')->name('assign-tenants');
    Route::post('/{collectorId}/unassign-tenants', [CollectorController::class, 'unassignTenants'])->where('collectorId', '[0-9]+')->name('unassign-tenants');
    Route::get('/{collectorId}/tenants', [CollectorController::class, 'assignedTenants'])->where('collectorId', '[0-9]+')->name('tenants');
});

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
    Route::post('/{invoice:uuid}/update-status', [InvoiceController::class, 'updateStatus'])->name('update-status');
    Route::get('/{invoice:uuid}/download', [InvoiceController::class, 'downloadPdf'])->name('download');
});

