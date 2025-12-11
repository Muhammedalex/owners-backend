<?php

use App\Http\Controllers\Api\V1\Report\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Reports API Routes - V1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/reports
| All routes require authentication and ownership scope
|
*/

Route::prefix('reports')->name('v1.reports.')->middleware(['auth:sanctum', 'ownership.scope'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [ReportController::class, 'dashboard'])->name('dashboard');
    
    // Tenants Reports
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/summary', [ReportController::class, 'tenantsSummary'])->name('summary');
        Route::get('/ratings', [ReportController::class, 'tenantsRatings'])->name('ratings');
        Route::get('/employment', [ReportController::class, 'tenantsEmployment'])->name('employment');
        Route::get('/top', [ReportController::class, 'topTenants'])->name('top');
    });
    
    // Contracts Reports
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/summary', [ReportController::class, 'contractsSummary'])->name('summary');
        Route::get('/status', [ReportController::class, 'contractsStatus'])->name('status');
        Route::get('/financial', [ReportController::class, 'contractsFinancial'])->name('financial');
        Route::get('/expiring', [ReportController::class, 'expiringContracts'])->name('expiring');
    });
    
    // Invoices Reports
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/summary', [ReportController::class, 'invoicesSummary'])->name('summary');
        Route::get('/status', [ReportController::class, 'invoicesStatus'])->name('status');
        Route::get('/overdue', [ReportController::class, 'overdueInvoices'])->name('overdue');
    });
    
    // Revenue Reports
    Route::prefix('revenue')->name('revenue.')->group(function () {
        Route::get('/monthly', [ReportController::class, 'monthlyRevenue'])->name('monthly');
        Route::get('/period', [ReportController::class, 'revenueByPeriod'])->name('period');
    });
    
    // Payments Reports
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/summary', [ReportController::class, 'paymentsSummary'])->name('summary');
        Route::get('/methods', [ReportController::class, 'paymentMethods'])->name('methods');
    });
    
    // Cache Management
    Route::post('/cache/clear', [ReportController::class, 'clearCache'])->name('cache.clear');
});

