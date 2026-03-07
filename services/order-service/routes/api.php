<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Service – API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api (RouteServiceProvider) and versioned
| under /v1.  The X-Tenant-ID header is injected by the API Gateway.
|--------------------------------------------------------------------------
*/

// Health check (used by Docker healthcheck and load balancers).
Route::get('/health', [OrderController::class, 'health'])->name('health');

// ---------------------------------------------------------------------------
// v1 – Order endpoints
// ---------------------------------------------------------------------------
Route::prefix('v1')->group(function () {
    // List orders (tenant-scoped, paginated)
    Route::get('/orders', [OrderController::class, 'index'])
        ->name('orders.index');

    // Create order → triggers Order Placement Saga
    Route::post('/orders', [OrderController::class, 'store'])
        ->name('orders.store');

    // Show order with saga transaction log
    Route::get('/orders/{id}', [OrderController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('orders.show');

    // Cancel a pending order
    Route::delete('/orders/{id}', [OrderController::class, 'cancel'])
        ->where('id', '[0-9]+')
        ->name('orders.cancel');

    // Real-time saga status (Redis → DB fallback)
    Route::get('/orders/{id}/saga-status', [OrderController::class, 'sagaStatus'])
        ->where('id', '[0-9]+')
        ->name('orders.saga-status');
});
