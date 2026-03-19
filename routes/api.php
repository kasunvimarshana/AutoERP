<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\FeatureFlagController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\ServiceAuthController;
use App\Http\Controllers\Api\V1\TenantConfigController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Middleware\AbacAuthorization;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\SuspiciousActivityDetection;
use App\Http\Middleware\VerifyTokenVersion;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {

    // ── Public Auth Endpoints (rate limited) ───────────────────────────
    Route::prefix('auth')->middleware(['throttle:auth'])->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware([SuspiciousActivityDetection::class])
            ->name('api.v1.auth.login');

        Route::post('register', [AuthController::class, 'register'])
            ->name('api.v1.auth.register');

        // Public key for local JWT verification by downstream microservices
        Route::get('public-key', [ServiceAuthController::class, 'publicKey'])
            ->name('api.v1.auth.public-key');
    });

    // ── Protected Auth Endpoints ───────────────────────────────────────
    Route::prefix('auth')->middleware(['auth:api', VerifyTokenVersion::class])->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');

        Route::post('logout-all', [AuthController::class, 'logoutAll'])
            ->name('api.v1.auth.logout-all');

        Route::post('refresh', [AuthController::class, 'refresh'])
            ->name('api.v1.auth.refresh');

        Route::get('me', [AuthController::class, 'me'])
            ->name('api.v1.auth.me');
    });

    // ── User Endpoints ─────────────────────────────────────────────────
    Route::prefix('user')
        ->middleware(['auth:api', 'throttle:api', VerifyTokenVersion::class, EnsureTenantContext::class])
        ->group(function (): void {
            Route::get('profile', [UserController::class, 'profile'])
                ->name('api.v1.user.profile');

            Route::put('profile', [UserController::class, 'update'])
                ->name('api.v1.user.profile.update');
        });

    // ── Device Endpoints ───────────────────────────────────────────────
    Route::prefix('devices')
        ->middleware(['auth:api', 'throttle:api', VerifyTokenVersion::class])
        ->group(function (): void {
            Route::get('/', [DeviceController::class, 'index'])
                ->name('api.v1.devices.index');

            Route::delete('{deviceId}', [DeviceController::class, 'revoke'])
                ->name('api.v1.devices.revoke');
        });

    // ── Feature Flag Check (public + authenticated) ────────────────────
    Route::get('feature-flags/{name}/check', [FeatureFlagController::class, 'check'])
        ->middleware(['throttle:api'])
        ->name('api.v1.feature-flags.check');

    // ── Service-to-Service Auth ────────────────────────────────────────
    Route::prefix('service')->middleware(['throttle:60,1'])->group(function (): void {
        Route::post('token', [ServiceAuthController::class, 'token'])
            ->name('api.v1.service.token');
    });

    // ── Admin Endpoints (require auth + ABAC active user/tenant) ──────
    Route::prefix('admin')
        ->middleware([
            'auth:api',
            VerifyTokenVersion::class,
            EnsureTenantContext::class,
            AbacAuthorization::class . ':require_active_user,require_active_tenant',
        ])
        ->group(function (): void {

            // Tenant Management
            Route::post('tenants', [TenantController::class, 'store'])
                ->name('api.v1.admin.tenants.store');

            Route::get('tenants/{tenantId}', [TenantController::class, 'show'])
                ->name('api.v1.admin.tenants.show');

            Route::put('tenants/{tenantId}', [TenantController::class, 'update'])
                ->name('api.v1.admin.tenants.update');

            // Tenant Runtime Configuration
            Route::get('tenants/{tenantId}/config', [TenantConfigController::class, 'index'])
                ->name('api.v1.admin.tenant-config.index');

            Route::put('tenants/{tenantId}/config/{key}', [TenantConfigController::class, 'set'])
                ->name('api.v1.admin.tenant-config.set');

            Route::delete('tenants/{tenantId}/config/{key}', [TenantConfigController::class, 'destroy'])
                ->name('api.v1.admin.tenant-config.destroy');

            // Role & Permission Management
            Route::get('roles', [RoleController::class, 'index'])
                ->name('api.v1.admin.roles.index');

            Route::post('roles', [RoleController::class, 'store'])
                ->name('api.v1.admin.roles.store');

            Route::put('roles/{roleId}', [RoleController::class, 'update'])
                ->name('api.v1.admin.roles.update');

            Route::get('permissions', [RoleController::class, 'permissions'])
                ->name('api.v1.admin.permissions.index');

            Route::post('permissions', [RoleController::class, 'createPermission'])
                ->name('api.v1.admin.permissions.store');

            // Feature Flags Management
            Route::get('feature-flags', [FeatureFlagController::class, 'index'])
                ->name('api.v1.admin.feature-flags.index');

            Route::post('feature-flags', [FeatureFlagController::class, 'upsert'])
                ->name('api.v1.admin.feature-flags.upsert');

            Route::delete('feature-flags/{name}', [FeatureFlagController::class, 'destroy'])
                ->name('api.v1.admin.feature-flags.destroy');

            // Service Client Registration (admin only)
            Route::post('services/register', [ServiceAuthController::class, 'register'])
                ->name('api.v1.admin.services.register');
        });
});

// ── Health Check ───────────────────────────────────────────────────────
Route::get('/health', function (): \Illuminate\Http\JsonResponse {
    return response()->json([
        'status'    => 'ok',
        'service'   => 'kv-sso',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('api.health');
