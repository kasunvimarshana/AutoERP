<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierAddressController;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierContactController;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierController;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierProductController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('suppliers', SupplierController::class);

    Route::get('suppliers/{supplier}/addresses', [SupplierAddressController::class, 'index']);
    Route::post('suppliers/{supplier}/addresses', [SupplierAddressController::class, 'store']);
    Route::put('suppliers/{supplier}/addresses/{address}', [SupplierAddressController::class, 'update']);
    Route::delete('suppliers/{supplier}/addresses/{address}', [SupplierAddressController::class, 'destroy']);

    Route::get('suppliers/{supplier}/contacts', [SupplierContactController::class, 'index']);
    Route::post('suppliers/{supplier}/contacts', [SupplierContactController::class, 'store']);
    Route::put('suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'update']);
    Route::delete('suppliers/{supplier}/contacts/{contact}', [SupplierContactController::class, 'destroy']);

    Route::get('suppliers/{supplier}/products', [SupplierProductController::class, 'index']);
    Route::post('suppliers/{supplier}/products', [SupplierProductController::class, 'store']);
    Route::put('suppliers/{supplier}/products/{supplierProduct}', [SupplierProductController::class, 'update']);
    Route::delete('suppliers/{supplier}/products/{supplierProduct}', [SupplierProductController::class, 'destroy']);
});
