<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status'  => 'ok',
    'service' => 'inventory-service',
    'version' => '1.0.0',
    'time'    => now()->toIso8601String(),
]));

Route::apiResource('products', ProductController::class);
Route::put('products/{id}/stock', [ProductController::class, 'stockUpdate']);

Route::get('inventory/reservations', [InventoryController::class, 'reservations']);
Route::get('inventory/reservations/{id}', [InventoryController::class, 'reservation']);
