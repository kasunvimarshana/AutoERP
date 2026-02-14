<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Modules\CRM\Http\Controllers\CustomerController;
use App\Modules\Inventory\Http\Controllers\ProductController;
use App\Modules\Inventory\Http\Controllers\ProductCategoryController;
use App\Modules\Billing\Http\Controllers\InvoiceController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth Routes (v1)
Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant.aware'])->group(function () {
    // CRM Routes
    Route::get('customers/search', [CustomerController::class, 'search']);
    Route::apiResource('customers', CustomerController::class);

    // Inventory Routes
    Route::apiResource('product-categories', ProductCategoryController::class);
    
    Route::get('products/search', [ProductController::class, 'search']);
    Route::get('products/{id}/inventory', [ProductController::class, 'inventory']);
    Route::post('products/{id}/adjust-stock', [ProductController::class, 'adjustStock']);
    Route::apiResource('products', ProductController::class);

    // Billing Routes
    Route::get('invoices/search', [InvoiceController::class, 'search']);
    Route::post('invoices/{id}/send', [InvoiceController::class, 'send']);
    Route::post('invoices/{id}/record-payment', [InvoiceController::class, 'recordPayment']);
    Route::get('invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf']);
    Route::apiResource('invoices', InvoiceController::class);
});

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});
