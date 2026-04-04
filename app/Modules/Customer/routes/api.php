<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerAddressController;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerController;

Route::get('/customers',         [CustomerController::class, 'index']);
Route::post('/customers',        [CustomerController::class, 'store']);
Route::get('/customers/{id}',    [CustomerController::class, 'show']);
Route::patch('/customers/{id}',  [CustomerController::class, 'update']);
Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

Route::get('/customer-addresses',         [CustomerAddressController::class, 'index']);
Route::post('/customer-addresses',        [CustomerAddressController::class, 'store']);
Route::get('/customer-addresses/{id}',    [CustomerAddressController::class, 'show']);
Route::patch('/customer-addresses/{id}',  [CustomerAddressController::class, 'update']);
Route::delete('/customer-addresses/{id}', [CustomerAddressController::class, 'destroy']);
