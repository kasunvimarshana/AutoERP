<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/code/{code}', [ProductController::class, 'showByCode']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/products/bulk', [ProductController::class, 'bulkGet']);

// Admin-protected routes
Route::middleware('auth.jwt')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});
