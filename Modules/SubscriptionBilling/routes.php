<?php

use Illuminate\Support\Facades\Route;
use Modules\SubscriptionBilling\Presentation\Controllers\SubscriptionPlanController;
use Modules\SubscriptionBilling\Presentation\Controllers\SubscriptionController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('subscriptions/plans', SubscriptionPlanController::class)
        ->except(['update']);
    Route::apiResource('subscriptions', SubscriptionController::class)
        ->except(['update', 'destroy']);
    Route::post('subscriptions/{id}/renew', [SubscriptionController::class, 'renew']);
    Route::post('subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);
});
