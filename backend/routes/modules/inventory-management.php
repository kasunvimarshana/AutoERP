<?php

use App\Modules\InventoryManagement\Http\Controllers\InventoryItemController;
use App\Modules\InventoryManagement\Http\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory Management API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api'])->group(function () {
    
    // Inventory Item routes
    Route::apiResource('inventory-items', InventoryItemController::class);
    Route::post('inventory-items/{id}/adjust-stock', [InventoryItemController::class, 'adjustStock']);

    // Purchase Order routes
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve']);
    Route::post('purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive']);
});
