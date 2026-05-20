<?php

use App\Http\Controllers\SystemUserController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware([])->group(function () {
    // System User
    Route::post('system-users', [SystemUserController::class, 'store']);
    Route::get('system-users', [SystemUserController::class, 'index']);
    Route::get('system-users/{system_user}', [SystemUserController::class, 'show']);
    Route::put('system-users/{system_user}', [SystemUserController::class, 'update']);
    Route::delete('system-users/{system_user}', [SystemUserController::class, 'delete']);

    // Supplier
    Route::post('suppliers', [SupplierController::class, 'store']);
    Route::get('suppliers', [SupplierController::class, 'index']);
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'delete']);

    // Customer
    Route::post('customers', [CustomerController::class, 'store']);
    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{customer}', [CustomerController::class, 'show']);
    Route::put('customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('customers/{customer}', [CustomerController::class, 'delete']);

    // Vehicle
    Route::post('vehicles', [VehicleController::class, 'store']);
    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::put('vehicles/{vehicle}', [VehicleController::class, 'update']);
    Route::delete('vehicles/{vehicle}', [VehicleController::class, 'delete']);
});
