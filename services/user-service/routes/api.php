<?php

use App\Modules\User\Routes\UserRoutes;
use App\Modules\Webhook\Routes\WebhookRoutes;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    UserRoutes::register();
    WebhookRoutes::register();
    Route::get('/health', [\App\Http\Controllers\HealthController::class, 'check']);
});
