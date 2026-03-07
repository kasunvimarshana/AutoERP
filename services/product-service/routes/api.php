<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Product Service – API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api by the RouteServiceProvider.
|
| Middleware groups:
|   auth:api      – Laravel Passport token authentication
|   tenant        – TenantMiddleware: resolve & bind current tenant
|   role:X        – CheckRole: require role X
|   permission:X  – CheckPermission: require permission X
|
*/

// -------------------------------------------------------------------------
// Health check (unauthenticated)
// -------------------------------------------------------------------------

Route::get('/health', HealthController::class);

// -------------------------------------------------------------------------
// Tenant-scoped, authenticated routes
// -------------------------------------------------------------------------

Route::middleware(['auth:api', 'tenant'])->group(function () {

    // ------------------------------------------------------------------
    // Products
    // ------------------------------------------------------------------
    Route::prefix('products')->group(function () {

        // List / search / filter products (any authenticated user)
        Route::get('/',          [ProductController::class, 'index']);

        // Low-stock report
        Route::get('/low-stock', [ProductController::class, 'lowStock']);

        // Batch fetch by IDs (called from Inventory service)
        Route::post('/batch',    [ProductController::class, 'batch']);

        // Single product read
        Route::get('/{id}',      [ProductController::class, 'show']);

        // Write operations require admin role
        Route::middleware('role:admin|super-admin')->group(function () {
            Route::post('/',         [ProductController::class, 'store'])
                 ->middleware('permission:create-products');

            Route::put('/{id}',      [ProductController::class, 'update'])
                 ->middleware('permission:edit-products');

            Route::patch('/{id}',    [ProductController::class, 'update'])
                 ->middleware('permission:edit-products');

            Route::delete('/{id}',   [ProductController::class, 'destroy'])
                 ->middleware('permission:delete-products');
        });
    });

    // ------------------------------------------------------------------
    // Categories
    // ------------------------------------------------------------------
    Route::prefix('categories')->group(function () {

        // List / search categories (any authenticated user)
        Route::get('/',              [CategoryController::class, 'index']);
        Route::get('/{id}',          [CategoryController::class, 'show']);

        // Products within a category
        Route::get('/{id}/products', [CategoryController::class, 'products']);

        // Write operations require admin role
        Route::middleware('role:admin|super-admin')->group(function () {
            Route::post('/',         [CategoryController::class, 'store'])
                 ->middleware('permission:create-categories');

            Route::put('/{id}',      [CategoryController::class, 'update'])
                 ->middleware('permission:edit-categories');

            Route::patch('/{id}',    [CategoryController::class, 'update'])
                 ->middleware('permission:edit-categories');

            Route::delete('/{id}',   [CategoryController::class, 'destroy'])
                 ->middleware('permission:delete-categories');
        });
    });
});
