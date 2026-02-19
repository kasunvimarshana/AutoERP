<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are registered here following API-first design principles.
| All routes require JWT authentication unless specified otherwise.
|
*/

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Authentication Routes (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('login', [\Modules\Auth\Http\Controllers\AuthController::class, 'login'])->name('api.auth.login');
        Route::post('register', [\Modules\Auth\Http\Controllers\AuthController::class, 'register'])->name('api.auth.register');
        Route::post('refresh', [\Modules\Auth\Http\Controllers\AuthController::class, 'refresh'])->name('api.auth.refresh');

        // Protected routes
        Route::middleware([\Modules\Auth\Http\Middleware\JwtAuthMiddleware::class])->group(function () {
            Route::post('logout', [\Modules\Auth\Http\Controllers\AuthController::class, 'logout'])->name('api.auth.logout');
            Route::get('me', [\Modules\Auth\Http\Controllers\AuthController::class, 'me'])->name('api.auth.me');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Protected API Routes
    |--------------------------------------------------------------------------
    |
    | All routes below require JWT authentication and tenant context
    |
    */
    Route::middleware([
        \Modules\Auth\Http\Middleware\JwtAuthMiddleware::class,
        \Modules\Tenant\Http\Middleware\TenantMiddleware::class,
    ])->group(function () {

        /*
        |----------------------------------------------------------------------
        | Tenant Management
        |----------------------------------------------------------------------
        */
        Route::apiResource('tenants', \Modules\Tenant\Http\Controllers\TenantController::class);
        Route::post('tenants/{id}/restore', [\Modules\Tenant\Http\Controllers\TenantController::class, 'restore'])->name('api.tenants.restore');

        /*
        |----------------------------------------------------------------------
        | Organization Management
        |----------------------------------------------------------------------
        */
        Route::apiResource('organizations', \Modules\Tenant\Http\Controllers\OrganizationController::class);
        Route::post('organizations/{id}/restore', [\Modules\Tenant\Http\Controllers\OrganizationController::class, 'restore'])->name('api.organizations.restore');

        // Organization hierarchical endpoints
        Route::get('organizations/{organization}/children', [\Modules\Tenant\Http\Controllers\OrganizationController::class, 'children'])->name('api.organizations.children');
        Route::get('organizations/{organization}/ancestors', [\Modules\Tenant\Http\Controllers\OrganizationController::class, 'ancestors'])->name('api.organizations.ancestors');
        Route::get('organizations/{organization}/descendants', [\Modules\Tenant\Http\Controllers\OrganizationController::class, 'descendants'])->name('api.organizations.descendants');
        Route::put('organizations/{organization}/move', [\Modules\Tenant\Http\Controllers\OrganizationController::class, 'move'])->name('api.organizations.move');

        /*
        |----------------------------------------------------------------------
        | User & Role Management
        |----------------------------------------------------------------------
        */
        Route::apiResource('users', \Modules\Auth\Http\Controllers\UserController::class);
        Route::apiResource('roles', \Modules\Auth\Http\Controllers\RoleController::class);
        Route::apiResource('permissions', \Modules\Auth\Http\Controllers\PermissionController::class);

        // Current user device management
        Route::prefix('devices')->group(function () {
            Route::get('/', [\Modules\Auth\Http\Controllers\UserDeviceController::class, 'index'])->name('api.devices.index');
            Route::delete('others', [\Modules\Auth\Http\Controllers\UserDeviceController::class, 'destroyOthers'])->name('api.devices.destroy-others');
            Route::get('{device}', [\Modules\Auth\Http\Controllers\UserDeviceController::class, 'show'])->name('api.devices.show');
            Route::delete('{device}', [\Modules\Auth\Http\Controllers\UserDeviceController::class, 'destroy'])->name('api.devices.destroy');
        });

        // Admin user device management
        Route::prefix('users/{user}/devices')->group(function () {
            Route::get('/', [\Modules\Auth\Http\Controllers\UserDeviceController::class, 'userDevices'])->name('api.users.devices.index');
            Route::delete('/', [\Modules\Auth\Http\Controllers\UserDeviceController::class, 'destroyAll'])->name('api.users.devices.destroy-all');
        });

        // Role permissions management
        Route::prefix('roles/{role}/permissions')->group(function () {
            Route::post('attach', [\Modules\Auth\Http\Controllers\RoleController::class, 'attachPermissions'])->name('api.roles.permissions.attach');
            Route::post('detach', [\Modules\Auth\Http\Controllers\RoleController::class, 'detachPermissions'])->name('api.roles.permissions.detach');
            Route::post('sync', [\Modules\Auth\Http\Controllers\RoleController::class, 'syncPermissions'])->name('api.roles.permissions.sync');
        });

        /*
        |----------------------------------------------------------------------
        | Product Management
        |----------------------------------------------------------------------
        */
        Route::apiResource('products', \Modules\Product\Http\Controllers\ProductController::class);
        Route::apiResource('product-categories', \Modules\Product\Http\Controllers\ProductCategoryController::class);
        Route::apiResource('units', \Modules\Product\Http\Controllers\UnitController::class);

        // Product bundles
        Route::prefix('products/{product}/bundles')->group(function () {
            Route::get('/', [\Modules\Product\Http\Controllers\ProductController::class, 'getBundleItems'])->name('api.products.bundles.index');
            Route::post('/', [\Modules\Product\Http\Controllers\ProductController::class, 'addBundleItem'])->name('api.products.bundles.store');
            Route::delete('{bundleItem}', [\Modules\Product\Http\Controllers\ProductController::class, 'removeBundleItem'])->name('api.products.bundles.destroy');
        });

        // Product composites
        Route::prefix('products/{product}/composites')->group(function () {
            Route::get('/', [\Modules\Product\Http\Controllers\ProductController::class, 'getCompositeParts'])->name('api.products.composites.index');
            Route::post('/', [\Modules\Product\Http\Controllers\ProductController::class, 'addCompositePart'])->name('api.products.composites.store');
            Route::delete('{compositePart}', [\Modules\Product\Http\Controllers\ProductController::class, 'removeCompositePart'])->name('api.products.composites.destroy');
        });

        // Product category hierarchical endpoints
        Route::get('product-categories/{productCategory}/children', [\Modules\Product\Http\Controllers\ProductCategoryController::class, 'getChildren'])->name('api.product-categories.children');
        Route::get('product-categories/{productCategory}/products', [\Modules\Product\Http\Controllers\ProductCategoryController::class, 'getProducts'])->name('api.product-categories.products');

        // Unit conversions
        Route::prefix('units/{unit}/conversions')->group(function () {
            Route::get('/', [\Modules\Product\Http\Controllers\UnitController::class, 'getConversions'])->name('api.units.conversions.index');
            Route::post('/', [\Modules\Product\Http\Controllers\UnitController::class, 'addConversion'])->name('api.units.conversions.store');
        });

        // Unit conversion calculation
        Route::post('units/convert', [\Modules\Product\Http\Controllers\UnitController::class, 'convert'])->name('api.units.convert');

        /*
        |----------------------------------------------------------------------
        | Pricing Management
        |----------------------------------------------------------------------
        */
        Route::prefix('products/{product}/prices')->group(function () {
            Route::get('/', [\Modules\Pricing\Http\Controllers\PricingController::class, 'index'])->name('api.products.prices.index');
            Route::post('/', [\Modules\Pricing\Http\Controllers\PricingController::class, 'store'])->name('api.products.prices.store');
            Route::put('{price}', [\Modules\Pricing\Http\Controllers\PricingController::class, 'update'])->name('api.products.prices.update');
            Route::delete('{price}', [\Modules\Pricing\Http\Controllers\PricingController::class, 'destroy'])->name('api.products.prices.destroy');
        });

        // Price calculation
        Route::post('pricing/calculate', [\Modules\Pricing\Http\Controllers\PricingController::class, 'calculate'])->name('api.pricing.calculate');

        /*
        |----------------------------------------------------------------------
        | Audit Logs
        |----------------------------------------------------------------------
        */
        Route::get('audit-logs', [\Modules\Audit\Http\Controllers\AuditLogController::class, 'index'])->name('api.audit-logs.index');
        Route::get('audit-logs/{auditLog}', [\Modules\Audit\Http\Controllers\AuditLogController::class, 'show'])->name('api.audit-logs.show');
    });
});
