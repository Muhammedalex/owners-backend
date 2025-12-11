<?php

use App\Http\Controllers\Api\V1\Payment\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payments API Routes - V1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/payments
| All routes use UUID in route parameters
|
*/

Route::prefix('payments')->name('v1.payments.')->middleware(['auth:sanctum', 'ownership.scope'])->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/', [PaymentController::class, 'store'])->name('store');
    Route::get('/{payment:uuid}', [PaymentController::class, 'show'])->name('show');
    Route::put('/{payment:uuid}', [PaymentController::class, 'update'])->name('update');
    Route::patch('/{payment:uuid}', [PaymentController::class, 'update'])->name('update.patch');
    Route::delete('/{payment:uuid}', [PaymentController::class, 'destroy'])->name('destroy');
    Route::post('/{payment:uuid}/mark-as-paid', [PaymentController::class, 'markAsPaid'])->name('mark-as-paid');
    Route::post('/{payment:uuid}/mark-as-unpaid', [PaymentController::class, 'markAsUnpaid'])->name('mark-as-unpaid');
});

// Payments by Invoice (nested route)
Route::prefix('invoices')->name('v1.invoices.')->middleware(['auth:sanctum', 'ownership.scope'])->group(function () {
    Route::get('/{invoice:uuid}/payments', [PaymentController::class, 'getByInvoice'])->name('payments');
});

