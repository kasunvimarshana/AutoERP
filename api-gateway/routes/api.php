<?php

declare(strict_types=1);

use App\Http\Controllers\GatewayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Gateway Routes
|--------------------------------------------------------------------------
|
| All traffic enters through /api/{service}/{path?} and is proxied to
| the appropriate downstream microservice.
|
| Rate limiting is applied globally. Authentication is handled by the
| auth-service; the gateway forwards the Bearer token downstream.
|
*/

// ── Health check ──────────────────────────────────────────────────────────
Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'api-gateway']));

// ── Versioned proxy routes ────────────────────────────────────────────────
Route::prefix('v1')->middleware('rate.limit')->group(function () {

    // Auth service – public (no auth required at gateway level)
    Route::any('/{service}/{path?}', [GatewayController::class, 'proxy'])
        ->where('service', 'auth|inventory|orders|notifications')
        ->where('path', '.*');
});
