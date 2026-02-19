<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\PaymentController;
use Modules\Billing\Http\Controllers\PlanController;
use Modules\Billing\Http\Controllers\SubscriptionController;

/*
|--------------------------------------------------------------------------
| Billing API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'jwt.auth'])->group(function () {

    // Public Plans (no auth required for viewing public plans)
    Route::get('billing/public-plans', [PlanController::class, 'publicPlans'])->withoutMiddleware(['jwt.auth']);

    // Plans
    Route::apiResource('billing/plans', PlanController::class);
    Route::post('billing/plans/{id}/activate', [PlanController::class, 'activate']);
    Route::post('billing/plans/{id}/deactivate', [PlanController::class, 'deactivate']);

    // Subscriptions
    Route::apiResource('billing/subscriptions', SubscriptionController::class);
    Route::post('billing/subscriptions/{id}/renew', [SubscriptionController::class, 'renew']);
    Route::post('billing/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('billing/subscriptions/{id}/suspend', [SubscriptionController::class, 'suspend']);
    Route::post('billing/subscriptions/{id}/reactivate', [SubscriptionController::class, 'reactivate']);
    Route::post('billing/subscriptions/{id}/change-plan', [SubscriptionController::class, 'changePlan']);

    // Payments
    Route::get('billing/payments', [PaymentController::class, 'index']);
    Route::get('billing/payments/{id}', [PaymentController::class, 'show']);
    Route::post('billing/payments/{id}/process', [PaymentController::class, 'process']);
    Route::post('billing/payments/{id}/refund', [PaymentController::class, 'refund']);
});
