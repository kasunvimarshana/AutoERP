<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Service API Routes
|--------------------------------------------------------------------------
|
| Routes for Order Service CRUD operations.
| Each mutating operation (create, update, delete, confirm) participates in
| a distributed transaction with the Inventory Service using the Saga pattern.
|
*/

// Standard CRUD routes for order management
Route::apiResource('orders', OrderController::class);

// Additional order lifecycle routes
Route::prefix('orders')->group(function () {
    // Confirm a pending order and fulfill inventory stock
    Route::post('/{id}/confirm', [OrderController::class, 'confirm']);
});

