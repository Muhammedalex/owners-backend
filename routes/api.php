<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// Route::options('{any}', function () {
//     return response()->json([], 200);
// })->where('any', '.*');
// // V1 API Routes
Route::prefix('v1')->group(function () {
    // Broadcasting authentication route - /api/v1/broadcasting/auth
    Broadcast::routes(['middleware' => ['auth:sanctum']]);
    
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/users.php';
    require __DIR__.'/api/v1/roles.php';
    require __DIR__.'/api/v1/permissions.php';
    require __DIR__.'/api/v1/notifications.php';
    require __DIR__.'/api/v1/ownerships.php';
    require __DIR__.'/api/v1/tenants.php';
    require __DIR__.'/api/v1/contracts.php';
    require __DIR__.'/api/v1/invoices.php';
    require __DIR__.'/api/v1/payments.php';
    require __DIR__.'/api/v1/reports.php';
    require __DIR__.'/api/v1/settings.php';
    require __DIR__.'/api/v1/media.php';
    require __DIR__.'/api/v1/documents.php';
    // Add other V1 module routes here
});

// Test route (can be removed)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
