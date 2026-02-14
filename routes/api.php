<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'timestamp' => now()]);
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// API v1 Routes
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/register', [\App\Http\Controllers\Api\V1\AuthController::class, 'register']);
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware(['auth:sanctum', 'tenant.aware'])->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
        Route::get('/auth/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);
        
        // Tenant routes
        Route::apiResource('tenants', \App\Http\Controllers\Api\V1\TenantController::class);
        
        // User management
        Route::apiResource('users', \App\Http\Controllers\Api\V1\UserController::class);
        
        // Role and Permission management
        Route::apiResource('roles', \App\Http\Controllers\Api\V1\RoleController::class);
        Route::apiResource('permissions', \App\Http\Controllers\Api\V1\PermissionController::class);
        
        // Customer Management (CRM)
        Route::apiResource('customers', \App\Http\Controllers\Api\V1\CustomerController::class);
        
        // Inventory Management
        Route::apiResource('products', \App\Http\Controllers\Api\V1\ProductController::class);
        Route::apiResource('inventory', \App\Http\Controllers\Api\V1\InventoryController::class);
        Route::post('inventory/movements', [\App\Http\Controllers\Api\V1\InventoryController::class, 'recordMovement']);
        
        // Invoice Management
        Route::apiResource('invoices', \App\Http\Controllers\Api\V1\InvoiceController::class);
        
        // Analytics
        Route::get('analytics/dashboard', [\App\Http\Controllers\Api\V1\AnalyticsController::class, 'dashboard']);
    });
});
