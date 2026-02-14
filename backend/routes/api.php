<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| All routes are versioned and prefixed with /api/v1
|
*/

// API Version 1
Route::prefix('v1')->group(function () {
    
    // Public routes (no authentication required)
    Route::prefix('auth')->group(function () {
        Route::post('register', [App\Modules\Auth\Http\Controllers\AuthController::class, 'register']);
        Route::post('login', [App\Modules\Auth\Http\Controllers\AuthController::class, 'login']);
        Route::post('forgot-password', [App\Modules\Auth\Http\Controllers\AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [App\Modules\Auth\Http\Controllers\AuthController::class, 'resetPassword']);
    });

    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::post('logout', [App\Modules\Auth\Http\Controllers\AuthController::class, 'logout']);
            Route::get('user', [App\Modules\Auth\Http\Controllers\AuthController::class, 'user']);
            Route::put('profile', [App\Modules\Auth\Http\Controllers\AuthController::class, 'updateProfile']);
            Route::put('password', [App\Modules\Auth\Http\Controllers\AuthController::class, 'updatePassword']);
        });

        // Tenant Management (Admin only)
        Route::apiResource('tenants', App\Modules\Tenant\Http\Controllers\TenantController::class);

        // User Management
        Route::apiResource('users', App\Modules\User\Http\Controllers\UserController::class);
        Route::post('users/{id}/restore', [App\Modules\User\Http\Controllers\UserController::class, 'restore']);
        Route::post('users/{id}/assign-role', [App\Modules\User\Http\Controllers\UserController::class, 'assignRole']);
        Route::post('users/{id}/remove-role', [App\Modules\User\Http\Controllers\UserController::class, 'removeRole']);
        Route::post('users/{id}/sync-permissions', [App\Modules\User\Http\Controllers\UserController::class, 'syncPermissions']);

        // Role Management
        Route::apiResource('roles', App\Modules\Role\Http\Controllers\RoleController::class);
        Route::post('roles/{id}/permissions', [App\Modules\Role\Http\Controllers\RoleController::class, 'syncPermissions']);

        // Permission Management
        Route::get('permissions', [App\Modules\Permission\Http\Controllers\PermissionController::class, 'index']);
        Route::get('permissions/{id}', [App\Modules\Permission\Http\Controllers\PermissionController::class, 'show']);

        // Customer Management
        Route::apiResource('customers', App\Modules\Customer\Http\Controllers\CustomerController::class);
        Route::post('customers/{id}/restore', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'restore']);
        Route::get('customers/{id}/vehicles', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'vehicles']);
        Route::get('customers/{id}/history', [App\Modules\Customer\Http\Controllers\CustomerController::class, 'history']);

        // Vehicle Management
        Route::apiResource('vehicles', App\Modules\Vehicle\Http\Controllers\VehicleController::class);
        Route::post('vehicles/{id}/restore', [App\Modules\Vehicle\Http\Controllers\VehicleController::class, 'restore']);
        Route::get('vehicles/{id}/history', [App\Modules\Vehicle\Http\Controllers\VehicleController::class, 'history']);

        // Inventory Management
        Route::apiResource('inventory', App\Modules\Inventory\Http\Controllers\InventoryController::class);
        Route::get('inventory/{id}/ledger', [App\Modules\Inventory\Http\Controllers\InventoryController::class, 'ledger']);
        Route::post('inventory/adjust', [App\Modules\Inventory\Http\Controllers\InventoryController::class, 'adjust']);
        Route::post('inventory/transfer', [App\Modules\Inventory\Http\Controllers\InventoryController::class, 'transfer']);

        // Product Management
        Route::apiResource('products', App\Modules\Product\Http\Controllers\ProductController::class);
        Route::post('products/{id}/restore', [App\Modules\Product\Http\Controllers\ProductController::class, 'restore']);
        Route::get('products/{id}/variants', [App\Modules\Product\Http\Controllers\ProductController::class, 'variants']);
        Route::post('products/{id}/variants', [App\Modules\Product\Http\Controllers\ProductController::class, 'addVariant']);

        // Order Management
        Route::apiResource('orders', App\Modules\Order\Http\Controllers\OrderController::class);
        Route::post('orders/{id}/cancel', [App\Modules\Order\Http\Controllers\OrderController::class, 'cancel']);
        Route::post('orders/{id}/complete', [App\Modules\Order\Http\Controllers\OrderController::class, 'complete']);
        Route::get('orders/{id}/items', [App\Modules\Order\Http\Controllers\OrderController::class, 'items']);
        Route::post('orders/{id}/items', [App\Modules\Order\Http\Controllers\OrderController::class, 'addItem']);

        // Invoice Management
        Route::apiResource('invoices', App\Modules\Invoice\Http\Controllers\InvoiceController::class);
        Route::post('invoices/{id}/send', [App\Modules\Invoice\Http\Controllers\InvoiceController::class, 'send']);
        Route::get('invoices/{id}/pdf', [App\Modules\Invoice\Http\Controllers\InvoiceController::class, 'pdf']);
        Route::get('invoices/{id}/items', [App\Modules\Invoice\Http\Controllers\InvoiceController::class, 'items']);
        Route::post('invoices/{id}/items', [App\Modules\Invoice\Http\Controllers\InvoiceController::class, 'addItem']);
        Route::post('invoices/{id}/payments', [App\Modules\Invoice\Http\Controllers\InvoiceController::class, 'recordPayment']);

        // Payment Management
        Route::apiResource('payments', App\Modules\Payment\Http\Controllers\PaymentController::class);
        Route::post('payments/{id}/complete', [App\Modules\Payment\Http\Controllers\PaymentController::class, 'complete']);
        Route::post('payments/{id}/refund', [App\Modules\Payment\Http\Controllers\PaymentController::class, 'refund']);
        Route::get('payments/summary', [App\Modules\Payment\Http\Controllers\PaymentController::class, 'summary']);
    });
});
