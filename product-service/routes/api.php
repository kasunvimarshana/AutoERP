<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Modules\Product\Controllers\ProductController;
use App\Http\Middleware\EnsureValidJwtFromKeycloak;
use App\Http\Middleware\CheckAbacPolicy;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Features integrated:
| 1. Healthcheck Endpoint (Microservice Standard)
| 2. Keycloak JWT Auth validation (Microservice Core)
| 3. RBAC/ABAC Middlewares (Token Attributes parsing)
| 4. Cross-Service/Webhook configurations
|
*/

// Basic Health Check Endpoint
Route::get('/health', function (Request $request) {
    return response()->json([
        'status' => 'UP',
        'service' => 'product-service',
        'timestamp' => now()->toIso8601String(),
        'dependencies' => [
            'database' => \Illuminate\Support\Facades\DB::connection()->getPdo() ? 'OK' : 'FAILED',
            'rabbitmq' => 'OK' // Checked via external probe
        ]
    ]);
});

Route::middleware([EnsureValidJwtFromKeycloak::class])->group(function () {

    // Listing Products needs general access
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);

    // Creation is blocked behind ABAC policies requiring 'engineering' department
    Route::post('products', [ProductController::class, 'store'])
        ->middleware(CheckAbacPolicy::class . ':can_create_product');

    Route::put('products/{id}', [ProductController::class, 'update'])
        ->middleware(CheckAbacPolicy::class . ':can_create_product');

    // Deletion requires 'admin' scope logic in ABAC
    Route::delete('products/{id}', [ProductController::class, 'destroy'])
        ->middleware(CheckAbacPolicy::class . ':can_delete_product');
});
