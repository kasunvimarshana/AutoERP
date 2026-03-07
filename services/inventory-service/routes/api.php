<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Service – API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api by the RouteServiceProvider.
|
| Middleware groups:
|   auth:api     – Laravel Passport token authentication
|   tenant       – TenantMiddleware: resolve & bind current tenant
|   role:X       – CheckRole: require role X
|   permission:X – CheckPermission: require permission X
|
*/

// -------------------------------------------------------------------------
// Health check (unauthenticated)
// -------------------------------------------------------------------------

Route::get('/health', HealthController::class);

// -------------------------------------------------------------------------
// Tenant-scoped, authenticated routes
// -------------------------------------------------------------------------

Route::middleware(['auth:api', 'tenant'])->group(function () {

    // ------------------------------------------------------------------
    // Inventory
    // ------------------------------------------------------------------
    Route::prefix('inventory')->group(function () {

        // Specific named routes BEFORE the {id} wildcard
        Route::get('/low-stock',            [InventoryController::class, 'lowStock']);
        Route::get('/with-product-details', [InventoryController::class, 'withProductDetails']);
        Route::post('/transfer',            [InventoryController::class, 'transfer'])->middleware('permission:manage-inventory');
        Route::post('/reserve',             [InventoryController::class, 'reserve'])->middleware('permission:manage-inventory');
        Route::post('/release',             [InventoryController::class, 'release'])->middleware('permission:manage-inventory');

        // Standard CRUD
        Route::get('/',      [InventoryController::class, 'index']);
        Route::post('/',     [InventoryController::class, 'store'])->middleware('permission:create-inventory');
        Route::get('/{id}',  [InventoryController::class, 'show']);
        Route::put('/{id}',  [InventoryController::class, 'update'])->middleware('permission:edit-inventory');
        Route::patch('/{id}',[InventoryController::class, 'update'])->middleware('permission:edit-inventory');
        Route::delete('/{id}',[InventoryController::class, 'destroy'])->middleware('permission:delete-inventory');

        // Stock adjustment on a specific inventory record
        Route::post('/{id}/adjust', [InventoryController::class, 'adjust'])->middleware('permission:manage-inventory');
    });

    // ------------------------------------------------------------------
    // Stock Movements (read-only from the API layer)
    // ------------------------------------------------------------------
    Route::prefix('stock-movements')->group(function () {
        Route::get('/',               [StockMovementController::class, 'index']);
        Route::get('/by-reference',   [StockMovementController::class, 'byReference']);
        Route::get('/{id}',           [StockMovementController::class, 'show']);
    });

    // ------------------------------------------------------------------
    // Warehouses
    // ------------------------------------------------------------------
    Route::prefix('warehouses')->group(function () {
        Route::get('/',        [WarehouseController::class, 'index']);
        Route::post('/',       [WarehouseController::class, 'store'])->middleware('permission:manage-warehouses');
        Route::get('/{id}',    [WarehouseController::class, 'show']);
        Route::put('/{id}',    [WarehouseController::class, 'update'])->middleware('permission:manage-warehouses');
        Route::patch('/{id}',  [WarehouseController::class, 'update'])->middleware('permission:manage-warehouses');
        Route::delete('/{id}', [WarehouseController::class, 'destroy'])->middleware('permission:manage-warehouses');
    });
});
