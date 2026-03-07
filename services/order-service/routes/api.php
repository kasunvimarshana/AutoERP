<?php

use App\Modules\Order\Routes\OrderRoutes;
use App\Modules\Webhook\Routes\WebhookRoutes;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    OrderRoutes::register();
    WebhookRoutes::register();

    Route::get('/health', [\App\Http\Controllers\HealthController::class, 'check']);
});
