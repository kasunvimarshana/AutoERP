<?php
use Illuminate\Support\Facades\Route;
use Modules\Notification\Presentation\Controllers\NotificationController;
use Modules\Notification\Presentation\Controllers\NotificationTemplateController;
Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{id}', [NotificationController::class, 'show']);
    Route::put('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
    Route::apiResource('notification-templates', NotificationTemplateController::class);
});
