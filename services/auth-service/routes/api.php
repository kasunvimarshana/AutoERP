<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Auth Service v1
|--------------------------------------------------------------------------
*/

// Health check — no auth, no tenant middleware
Route::get('/health', HealthController::class)->name('health');

Route::prefix('v1')->group(function () {

    // -----------------------------------------------------------------------
    // Auth — Public endpoints (rate limited)
    // -----------------------------------------------------------------------
    Route::prefix('auth')->name('auth.')->group(function () {

        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:' . env('RATE_LIMIT_LOGIN', 5) . ',1')
            ->name('login');

        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:' . env('RATE_LIMIT_REGISTER', 3) . ',1')
            ->name('register');

        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:5,1')
            ->name('forgot-password');

        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:5,1')
            ->name('reset-password');
    });

    // -----------------------------------------------------------------------
    // Auth — Protected endpoints
    // -----------------------------------------------------------------------
    Route::prefix('auth')->name('auth.')->middleware('auth:api')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });

    // -----------------------------------------------------------------------
    // Tenants (super-admin only)
    // -----------------------------------------------------------------------
    Route::prefix('tenants')->name('tenants.')->middleware(['auth:api', 'rbac:super-admin'])->group(function () {

        Route::get('/', [TenantController::class, 'index'])->name('index');
        Route::post('/', [TenantController::class, 'store'])->name('store');
        Route::get('/{id}', [TenantController::class, 'show'])->name('show');
        Route::put('/{id}', [TenantController::class, 'update'])->name('update');
        Route::patch('/{id}', [TenantController::class, 'update'])->name('patch');
        Route::delete('/{id}', [TenantController::class, 'destroy'])->name('destroy');
    });

    // Switch tenant context — available to authenticated users
    Route::post('/tenants/{id}/switch', [TenantController::class, 'switchTenant'])
        ->middleware('auth:api')
        ->name('tenants.switch');

    // -----------------------------------------------------------------------
    // Users — tenant-scoped
    // -----------------------------------------------------------------------
    Route::prefix('users')->name('users.')->middleware(['auth:api', 'tenant'])->group(function () {

        Route::get('/', [UserController::class, 'index'])
            ->middleware('rbac:tenant-admin|manager')
            ->name('index');

        Route::get('/{user}', [UserController::class, 'show'])
            ->name('show');

        Route::put('/{user}', [UserController::class, 'update'])
            ->name('update');

        Route::patch('/{user}', [UserController::class, 'update'])
            ->name('patch');

        Route::put('/{user}/roles', [UserController::class, 'updateRoles'])
            ->middleware('rbac:tenant-admin|super-admin')
            ->name('roles');

        Route::put('/{user}/permissions', [UserController::class, 'updatePermissions'])
            ->middleware('rbac:tenant-admin|super-admin')
            ->name('permissions');
    });
});
