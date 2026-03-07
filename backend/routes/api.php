<?php
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Inventory\Controllers\InventoryController;
use App\Modules\Order\Controllers\OrderController;
use App\Modules\Product\Controllers\ProductController;
use App\Modules\Tenant\Controllers\TenantController;
use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/sso/callback', [AuthController::class, 'ssoCallback']);
});

// Authenticated routes
Route::middleware(['auth:api'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Tenant-scoped routes
    Route::middleware(['tenant'])->group(function () {
        Route::apiResource('users', UserController::class);

        Route::apiResource('products', ProductController::class);

        Route::apiResource('inventory', InventoryController::class);
        Route::post('inventory/{id}/adjust-stock', [InventoryController::class, 'adjustStock']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);
    });

    // Tenant management (admin only)
    Route::prefix('admin')->group(function () {
        Route::apiResource('tenants', TenantController::class);
        Route::get('tenants/{id}/config', [TenantController::class, 'getConfig']);
        Route::post('tenants/{id}/config', [TenantController::class, 'setConfig']);
    });
});
