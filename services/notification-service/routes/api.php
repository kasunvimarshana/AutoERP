<?php
declare(strict_types=1);
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Support\Facades\Route;
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'health'])->name('health');
    Route::get('/ping', [HealthController::class, 'ping'])->name('health.ping');
});
Route::prefix('v1/notifications')->group(function () {
    Route::post('/send', [NotificationController::class, 'send'])->name('notifications.send');
    Route::post('/webhooks', [NotificationController::class, 'registerWebhook'])->name('webhooks.register');
});
