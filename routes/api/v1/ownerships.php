<?php

use App\Http\Controllers\Api\V1\Ownership\BuildingController;
use App\Http\Controllers\Api\V1\Ownership\BuildingFloorController;
use App\Http\Controllers\Api\V1\Ownership\OwnershipBoardMemberController;
use App\Http\Controllers\Api\V1\Ownership\OwnershipController;
use App\Http\Controllers\Api\V1\Ownership\PortfolioController;
use App\Http\Controllers\Api\V1\Ownership\UnitController;
use App\Http\Controllers\Api\V1\Ownership\UnitImportExportController;
use App\Http\Controllers\Api\V1\Ownership\UserOwnershipMappingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ownerships API Routes - V1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/ownerships
| All routes use UUID in route parameters (not ID)
|
*/

// Ownership store route (without ownership.scope middleware)
Route::prefix('ownerships')->name('v1.ownerships.')->middleware('auth:sanctum')->group(function () {
    
});

Route::prefix('ownerships')->name('v1.ownerships.')->middleware(['auth:sanctum', 'ownership.scope'])->group(function () {
    // Ownership CRUD
    Route::get('/', [OwnershipController::class, 'index'])->name('index');

    Route::post('/', [OwnershipController::class, 'store'])->name('store');
    // Board Members (ownership comes from cookie scope) - MUST come before {ownership:uuid} routes
    Route::prefix('board-members')->name('board-members.')->group(function () {
        Route::get('/', [OwnershipBoardMemberController::class, 'index'])->name('index');
        Route::post('/', [OwnershipBoardMemberController::class, 'store'])->name('store');
        Route::get('/{boardMember}', [OwnershipBoardMemberController::class, 'show'])->name('show');
        Route::put('/{boardMember}', [OwnershipBoardMemberController::class, 'update'])->name('update');
        Route::patch('/{boardMember}', [OwnershipBoardMemberController::class, 'update'])->name('update.patch');
        Route::delete('/{boardMember}', [OwnershipBoardMemberController::class, 'destroy'])->name('destroy');
    });
    
    // User-Ownership Mapping (ownership comes from cookie scope) - MUST come before {ownership:uuid} routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserOwnershipMappingController::class, 'getOwnershipUsers'])->name('index');
        Route::post('/assign', [UserOwnershipMappingController::class, 'assign'])->name('assign');
        Route::delete('/{user:uuid}', [UserOwnershipMappingController::class, 'remove'])->name('remove');
    });
    
    // Portfolios (ownership comes from cookie scope) - MUST come before {ownership:uuid} routes
    Route::prefix('portfolios')->name('portfolios.')->group(function () {
        Route::get('/', [PortfolioController::class, 'index'])->name('index');
        Route::post('/', [PortfolioController::class, 'store'])->name('store');
        Route::get('/{portfolio:uuid}', [PortfolioController::class, 'show'])->name('show');
        Route::put('/{portfolio:uuid}', [PortfolioController::class, 'update'])->name('update');
        Route::patch('/{portfolio:uuid}', [PortfolioController::class, 'update'])->name('update.patch');
        Route::delete('/{portfolio:uuid}', [PortfolioController::class, 'destroy'])->name('destroy');
        Route::post('/{portfolio:uuid}/activate', [PortfolioController::class, 'activate'])->name('activate');
        Route::post('/{portfolio:uuid}/deactivate', [PortfolioController::class, 'deactivate'])->name('deactivate');
    });
    
    // Buildings (ownership comes from cookie scope) - MUST come before {ownership:uuid} routes
    Route::prefix('buildings')->name('buildings.')->group(function () {
        Route::get('/', [BuildingController::class, 'index'])->name('index');
        Route::post('/', [BuildingController::class, 'store'])->name('store');
        
        // Building Floors (MUST come before {building:uuid} routes to avoid route conflict)
        Route::prefix('floors')->name('floors.')->group(function () {
            Route::get('/', [BuildingFloorController::class, 'index'])->name('index');
            Route::post('/', [BuildingFloorController::class, 'store'])->name('store');
            Route::get('/{buildingFloor}', [BuildingFloorController::class, 'show'])->name('show');
            Route::put('/{buildingFloor}', [BuildingFloorController::class, 'update'])->name('update');
            Route::patch('/{buildingFloor}', [BuildingFloorController::class, 'update'])->name('update.patch');
            Route::delete('/{buildingFloor}', [BuildingFloorController::class, 'destroy'])->name('destroy');
            Route::post('/{buildingFloor}/activate', [BuildingFloorController::class, 'activate'])->name('activate');
            Route::post('/{buildingFloor}/deactivate', [BuildingFloorController::class, 'deactivate'])->name('deactivate');
        });
        
        // Building CRUD (must come after floors routes)
        Route::get('/{building:uuid}', [BuildingController::class, 'show'])->name('show');
        Route::put('/{building:uuid}', [BuildingController::class, 'update'])->name('update');
        Route::patch('/{building:uuid}', [BuildingController::class, 'update'])->name('update.patch');
        Route::delete('/{building:uuid}', [BuildingController::class, 'destroy'])->name('destroy');
        Route::post('/{building:uuid}/activate', [BuildingController::class, 'activate'])->name('activate');
        Route::post('/{building:uuid}/deactivate', [BuildingController::class, 'deactivate'])->name('deactivate');
    });
    
    // Units (ownership comes from cookie scope) - MUST come before {ownership:uuid} routes
    Route::prefix('units')->name('units.')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('index');
        Route::post('/', [UnitController::class, 'store'])->name('store');
        Route::get('/import/template', [UnitImportExportController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/import/template/{building:uuid}', [UnitImportExportController::class, 'downloadTemplateForBuilding'])->name('import.template.building');
        Route::post('/import', [UnitImportExportController::class, 'import'])->name('import');
        Route::get('/export', [UnitImportExportController::class, 'export'])->name('export');
        Route::get('/{unit:uuid}', [UnitController::class, 'show'])->name('show');
        Route::put('/{unit:uuid}', [UnitController::class, 'update'])->name('update');
        Route::patch('/{unit:uuid}', [UnitController::class, 'update'])->name('update.patch');
        Route::delete('/{unit:uuid}', [UnitController::class, 'destroy'])->name('destroy');
        Route::post('/{unit:uuid}/activate', [UnitController::class, 'activate'])->name('activate');
        Route::post('/{unit:uuid}/deactivate', [UnitController::class, 'deactivate'])->name('deactivate');
        
        // Import/Export routes
        
    });
    
    // Ownership actions with UUID (must come after specific routes)
    Route::get('/{ownership:uuid}', [OwnershipController::class, 'show'])->name('show');
    Route::put('/{ownership:uuid}', [OwnershipController::class, 'update'])->name('update');
    Route::patch('/{ownership:uuid}', [OwnershipController::class, 'update'])->name('update.patch');
    Route::delete('/{ownership:uuid}', [OwnershipController::class, 'destroy'])->name('destroy');
    Route::post('/{ownership:uuid}/activate', [OwnershipController::class, 'activate'])->name('activate');
    Route::post('/{ownership:uuid}/deactivate', [OwnershipController::class, 'deactivate'])->name('deactivate');
    Route::post('/{ownership:uuid}/switch', [OwnershipController::class, 'switch'])->name('switch');
});

// User ownerships (outside ownership scope - user can view their own ownerships)
Route::prefix('users')->name('v1.users.')->middleware('auth:sanctum')->group(function () {
    Route::get('/{user:uuid}/ownerships', [UserOwnershipMappingController::class, 'getUserOwnerships'])->name('ownerships');
    Route::post('/{user:uuid}/ownerships/{ownership:uuid}/set-default', [UserOwnershipMappingController::class, 'setDefault'])->name('ownerships.set-default');
});

