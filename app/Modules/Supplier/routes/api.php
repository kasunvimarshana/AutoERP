<?php

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierContactController;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierController;

Route::get('/suppliers',         [SupplierController::class, 'index']);
Route::post('/suppliers',        [SupplierController::class, 'store']);
Route::get('/suppliers/{id}',    [SupplierController::class, 'show']);
Route::patch('/suppliers/{id}',  [SupplierController::class, 'update']);
Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

Route::get('/supplier-contacts',         [SupplierContactController::class, 'index']);
Route::post('/supplier-contacts',        [SupplierContactController::class, 'store']);
Route::get('/supplier-contacts/{id}',    [SupplierContactController::class, 'show']);
Route::patch('/supplier-contacts/{id}',  [SupplierContactController::class, 'update']);
Route::delete('/supplier-contacts/{id}', [SupplierContactController::class, 'destroy']);
