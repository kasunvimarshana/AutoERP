<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationChannelController;
use Modules\Notification\Http\Controllers\NotificationController;
use Modules\Notification\Http\Controllers\NotificationTemplateController;

/*
|--------------------------------------------------------------------------
| Notification API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->middleware(['api', 'jwt.auth'])->group(function () {

    // User Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::get('notifications/unread/count', [NotificationController::class, 'unreadCount']);

    // Templates (Admin)
    Route::apiResource('notification-templates', NotificationTemplateController::class);
    Route::post('notification-templates/{template}/activate', [NotificationTemplateController::class, 'activate']);
    Route::post('notification-templates/{template}/deactivate', [NotificationTemplateController::class, 'deactivate']);

    // Channels (Admin)
    Route::apiResource('notification-channels', NotificationChannelController::class);
    Route::post('notification-channels/{channel}/activate', [NotificationChannelController::class, 'activate']);
    Route::post('notification-channels/{channel}/deactivate', [NotificationChannelController::class, 'deactivate']);
    Route::post('notification-channels/{channel}/test', [NotificationChannelController::class, 'test']);
});
