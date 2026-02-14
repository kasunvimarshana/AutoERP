<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Procurement\Http\Controllers\SupplierController;
use App\Modules\Procurement\Http\Controllers\PurchaseOrderController;

Route::prefix('api/procurement')->middleware(['api'])->group(function () {
    // Supplier routes
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::put('/{id}', [SupplierController::class, 'update']);
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
    });

    // Purchase Order routes
    Route::prefix('purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index']);
        Route::post('/', [PurchaseOrderController::class, 'store']);
        Route::get('/{id}', [PurchaseOrderController::class, 'show']);
        Route::put('/{id}', [PurchaseOrderController::class, 'update']);
        Route::delete('/{id}', [PurchaseOrderController::class, 'destroy']);
        
        // Action routes
        Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve']);
    });
});
