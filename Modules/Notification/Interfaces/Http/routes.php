<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notification\Interfaces\Http\Controllers\NotificationController;
use Modules\Notification\Interfaces\Http\Controllers\NotificationTemplateController;

Route::prefix('api/v1/notifications')->group(function (): void {
    // Templates
    Route::get('/templates', [NotificationTemplateController::class, 'index']);
    Route::post('/templates', [NotificationTemplateController::class, 'store']);
    Route::get('/templates/{id}', [NotificationTemplateController::class, 'show']);
    Route::put('/templates/{id}', [NotificationTemplateController::class, 'update']);
    Route::delete('/templates/{id}', [NotificationTemplateController::class, 'destroy']);

    // Notifications
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/send', [NotificationController::class, 'send']);
    Route::get('/unread', [NotificationController::class, 'unread']);
    Route::get('/{id}', [NotificationController::class, 'show']);
    Route::put('/{id}/read', [NotificationController::class, 'markRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});
