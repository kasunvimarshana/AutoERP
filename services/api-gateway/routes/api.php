<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GatewayController;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ---------------------------------------------------------------------------
// Public authentication routes (no tenant context required)
// ---------------------------------------------------------------------------
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->name('auth.register');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('auth.login');
});

// ---------------------------------------------------------------------------
// Authenticated authentication routes
// ---------------------------------------------------------------------------
Route::prefix('auth')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->name('auth.refresh');

        Route::get('/me', [AuthController::class, 'me'])
            ->name('auth.me');
    });

// ---------------------------------------------------------------------------
// Versioned proxy routes — authenticated + tenant-scoped + rate-limited
//
// Single wildcard route: /v1/{service}/{path?}
//   service  → orders | inventory | payments | notifications
//   path     → anything after the service segment (passed to upstream)
// ---------------------------------------------------------------------------
Route::prefix('v1')
    ->middleware([
        'auth:api',
        TenantMiddleware::class,
        RateLimitMiddleware::class,
    ])
    ->group(function () {
        Route::any('/{service}/{path?}', [GatewayController::class, 'proxy'])
            ->where([
                'service' => 'orders|inventory|payments|notifications',
                'path'    => '.*',
            ])
            ->name('proxy');
    });
