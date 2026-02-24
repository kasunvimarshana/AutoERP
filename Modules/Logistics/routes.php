<?php

use Illuminate\Support\Facades\Route;
use Modules\Logistics\Presentation\Controllers\CarrierController;
use Modules\Logistics\Presentation\Controllers\DeliveryOrderController;
use Modules\Logistics\Presentation\Controllers\TrackingEventController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('logistics/carriers', CarrierController::class);
    Route::apiResource('logistics/delivery-orders', DeliveryOrderController::class);
    Route::post('logistics/delivery-orders/{id}/dispatch', [DeliveryOrderController::class, 'dispatch']);
    Route::post('logistics/delivery-orders/{id}/complete', [DeliveryOrderController::class, 'complete']);
    Route::get('logistics/delivery-orders/{id}/tracking', [DeliveryOrderController::class, 'tracking']);
    Route::post('logistics/tracking-events', [TrackingEventController::class, 'store']);
});
