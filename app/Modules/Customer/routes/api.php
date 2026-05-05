<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerAddressController;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerContactController;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function (): void {
    Route::apiResource('customers', CustomerController::class);

    Route::get('customers/{customer}/addresses', [CustomerAddressController::class, 'index']);
    Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store']);
    Route::put('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update']);
    Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy']);

    Route::get('customers/{customer}/contacts', [CustomerContactController::class, 'index']);
    Route::post('customers/{customer}/contacts', [CustomerContactController::class, 'store']);
    Route::put('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'update']);
    Route::delete('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'destroy']);
});
