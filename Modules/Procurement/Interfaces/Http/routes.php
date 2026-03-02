<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Procurement\Interfaces\Http\Controllers\PurchaseOrderController;
use Modules\Procurement\Interfaces\Http\Controllers\SupplierController;

Route::prefix('api/v1')->group(function (): void {
    // Supplier routes
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

    // Purchase Order routes
    Route::get('/procurement/orders', [PurchaseOrderController::class, 'index']);
    Route::post('/procurement/orders', [PurchaseOrderController::class, 'store']);
    Route::get('/procurement/orders/{id}', [PurchaseOrderController::class, 'show']);
    Route::post('/procurement/orders/{id}/confirm', [PurchaseOrderController::class, 'confirm']);
    Route::post('/procurement/orders/{id}/receive', [PurchaseOrderController::class, 'receive']);
    Route::post('/procurement/orders/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
    Route::delete('/procurement/orders/{id}', [PurchaseOrderController::class, 'destroy']);
});
