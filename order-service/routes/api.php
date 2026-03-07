<?php

declare(strict_types=1);

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'order-service']));

    Route::middleware('tenant')->group(function () {
        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
    });
});
