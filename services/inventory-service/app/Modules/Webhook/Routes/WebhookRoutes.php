<?php

namespace App\Modules\Webhook\Routes;

use App\Modules\Webhook\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

class WebhookRoutes
{
    public static function register(): void
    {
        Route::post('/webhooks', [WebhookController::class, 'receive']);
    }
}
