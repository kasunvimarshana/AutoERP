<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Health checks
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'check']);
    Route::get('/live', [HealthController::class, 'live']);
    Route::get('/ready', [HealthController::class, 'ready']);
});

// Receive incoming webhook from external system (no tenant required)
Route::post('/webhooks/receive', [WebhookController::class, 'receive']);

// Webhook management (tenant-aware)
Route::prefix('webhooks')->middleware('tenant')->group(function () {
    Route::get('/', [WebhookController::class, 'index']);
    Route::post('/', [WebhookController::class, 'store']);
    Route::put('/{id}', [WebhookController::class, 'update']);
    Route::delete('/{id}', [WebhookController::class, 'destroy']);
    Route::get('/{id}/deliveries', [WebhookController::class, 'deliveries']);
});

// Notification management
Route::prefix('notifications')->middleware('tenant')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/send', [NotificationController::class, 'send']);
});
