<?php

use App\Modules\Product\Routes\ProductRoutes;
use App\Modules\Webhook\Routes\WebhookRoutes;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    ProductRoutes::register();
    WebhookRoutes::register();

    Route::get('/health', [\App\Http\Controllers\HealthController::class, 'check']);
});
