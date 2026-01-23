<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;
use Modules\Inventory\Http\Controllers\PurchaseOrderController;
use Modules\Inventory\Http\Controllers\SupplierController;

/*
|--------------------------------------------------------------------------
| Inventory Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Inventory module.
| All routes are prefixed with 'api/v1' and require authentication.
|
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Supplier Routes - Non-parameterized routes MUST come before apiResource
    Route::get('suppliers/search', [SupplierController::class, 'search'])
        ->name('suppliers.search');

    // Supplier CRUD
    Route::apiResource('suppliers', SupplierController::class);

    // Inventory Item Routes - Non-parameterized routes MUST come before apiResource
    Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])
        ->name('inventory.low-stock');
    Route::get('inventory/reorder-suggestions', [InventoryController::class, 'reorderSuggestions'])
        ->name('inventory.reorder-suggestions');
    Route::post('inventory/transfer', [InventoryController::class, 'transferStock'])
        ->name('inventory.transfer');

    // Inventory CRUD
    Route::apiResource('inventory', InventoryController::class);

    // Stock Adjustment Routes
    Route::post('inventory/{id}/adjust', [InventoryController::class, 'adjustStock'])
        ->name('inventory.adjust');

    // Purchase Order Routes - Non-parameterized routes MUST come before apiResource
    Route::get('purchase-orders/search', [PurchaseOrderController::class, 'search'])
        ->name('purchase-orders.search');

    // Purchase Order CRUD
    Route::apiResource('purchase-orders', PurchaseOrderController::class);

    // Additional Purchase Order Routes
    Route::post('purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve'])
        ->name('purchase-orders.approve');
    Route::post('purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receiveItems'])
        ->name('purchase-orders.receive');
});
