<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Service – API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api by the RouteServiceProvider.
|
| Middleware groups:
|   auth:api        – Laravel Passport token authentication
|   tenant          – TenantMiddleware: resolve & bind current tenant
|   role:admin      – CheckRole: require 'admin' role
|   permission:X    – CheckPermission: require permission X
|
*/

// -------------------------------------------------------------------------
// Health check (unauthenticated)
// -------------------------------------------------------------------------

Route::get('/health', HealthController::class);

// -------------------------------------------------------------------------
// Auth endpoints (unauthenticated)
// -------------------------------------------------------------------------

Route::prefix('auth')->group(function () {
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/sso',     [AuthController::class, 'ssoLogin']);

    // Protected auth actions
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me',       [AuthController::class, 'me']);
    });
});

// -------------------------------------------------------------------------
// Tenant-scoped, authenticated routes
// -------------------------------------------------------------------------

Route::middleware(['auth:api', 'tenant'])->group(function () {

    // ------------------------------------------------------------------
    // Current user profile (any authenticated user)
    // ------------------------------------------------------------------
    Route::get('/me',  [UserController::class, 'profile']);
    Route::put('/me',  [UserController::class, 'updateProfile']);

    // ------------------------------------------------------------------
    // User management (admin+)
    // ------------------------------------------------------------------
    Route::prefix('users')->middleware('role:admin|super-admin')->group(function () {
        Route::get('/',                                    [UserController::class, 'index']);
        Route::post('/',                                   [UserController::class, 'store'])->middleware('permission:create-users');
        Route::get('/{id}',                                [UserController::class, 'show']);
        Route::put('/{id}',                                [UserController::class, 'update'])->middleware('permission:edit-users');
        Route::patch('/{id}',                              [UserController::class, 'update'])->middleware('permission:edit-users');
        Route::delete('/{id}',                             [UserController::class, 'destroy'])->middleware('permission:delete-users');
        Route::post('/{id}/roles',                         [UserController::class, 'assignRoles'])->middleware('permission:manage-roles');
        Route::post('/{id}/permissions',                   [UserController::class, 'assignPermissions'])->middleware('permission:manage-permissions');
    });

    // ------------------------------------------------------------------
    // Role management
    // ------------------------------------------------------------------
    Route::prefix('roles')->middleware('role:admin|super-admin')->group(function () {
        Route::get('/',        [RoleController::class, 'index']);
        Route::post('/',       [RoleController::class, 'store'])->middleware('permission:manage-roles');
        Route::get('/{id}',    [RoleController::class, 'show']);
        Route::put('/{id}',    [RoleController::class, 'update'])->middleware('permission:manage-roles');
        Route::patch('/{id}',  [RoleController::class, 'update'])->middleware('permission:manage-roles');
        Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('permission:manage-roles');
    });

    // Permissions listing (read-only; admin and above)
    Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('role:admin|super-admin');

    // ------------------------------------------------------------------
    // Tenant management (super-admin only)
    // ------------------------------------------------------------------
    Route::prefix('tenants')->middleware('role:super-admin')->group(function () {
        Route::get('/',                    [TenantController::class, 'index']);
        Route::post('/',                   [TenantController::class, 'store']);
        Route::get('/{id}',                [TenantController::class, 'show']);
        Route::put('/{id}',                [TenantController::class, 'update']);
        Route::patch('/{id}',              [TenantController::class, 'update']);
        Route::delete('/{id}',             [TenantController::class, 'destroy']);
        Route::get('/{id}/config',         [TenantController::class, 'getConfig']);
        Route::patch('/{id}/config',       [TenantController::class, 'updateConfig']);
    });
});
