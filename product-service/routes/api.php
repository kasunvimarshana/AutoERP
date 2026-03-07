<?php

/*
|--------------------------------------------------------------------------
| Product Service - API Routes
|--------------------------------------------------------------------------
|
| This file defines all API routes for the Product Service.
| Products support full CRUD with related inventory data fetched
| cross-service via event-driven messaging (RabbitMQ).
|
*/

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Product Routes
    |--------------------------------------------------------------------------
    |
    | GET    /api/v1/products          - List all products (with inventory)
    | POST   /api/v1/products          - Create a new product
    | GET    /api/v1/products/{id}     - Get a single product (with inventory)
    | PUT    /api/v1/products/{id}     - Update a product
    | DELETE /api/v1/products/{id}     - Delete a product (cascades to inventory)
    |
    */
    Route::apiResource('products', ProductController::class);
});
