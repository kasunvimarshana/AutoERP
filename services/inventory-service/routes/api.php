<?php

use App\Modules\Inventory\Routes\InventoryRoutes;
use App\Modules\Webhook\Routes\WebhookRoutes;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    InventoryRoutes::register();
    WebhookRoutes::register();

    Route::get('/health', [\App\Http\Controllers\HealthController::class, 'check']);
});
