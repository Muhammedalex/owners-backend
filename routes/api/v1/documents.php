<?php

use App\Http\Controllers\Api\V1\Document\DocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Documents API Routes - V1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/documents
| All routes require authentication
| Ownership scope middleware applied where needed
|
*/

Route::prefix('documents')->name('v1.documents.')->middleware(['auth:sanctum'])->group(function () {
    // Upload document
    Route::post('/upload', [DocumentController::class, 'upload'])
        ->middleware('ownership.scope')
        ->name('upload');
    
    // List documents
    Route::get('/', [DocumentController::class, 'index'])
        ->middleware('ownership.scope')
        ->name('index');
    
    // Download document
    Route::get('/{id}/download', [DocumentController::class, 'download'])
        ->middleware('ownership.scope')
        ->name('download');
    
    // CRUD operations
    Route::get('/{id}', [DocumentController::class, 'show'])
        ->middleware('ownership.scope')
        ->name('show');
    
    Route::put('/{id}', [DocumentController::class, 'update'])
        ->middleware('ownership.scope')
        ->name('update');
    
    Route::delete('/{id}', [DocumentController::class, 'destroy'])
        ->middleware('ownership.scope')
        ->name('destroy');
});

