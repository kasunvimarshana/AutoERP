<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Infrastructure\Http\Controllers\NotificationController;
use Modules\Notification\Infrastructure\Http\Controllers\NotificationPreferenceController;
use Modules\Notification\Infrastructure\Http\Controllers\NotificationTemplateController;

Route::prefix('api')->group(function () {

    // ── User inbox ────────────────────────────────────────────────────────────
    Route::get('notifications',               [NotificationController::class, 'index']);
    Route::get('notifications/unread-count',  [NotificationController::class, 'unreadCount']);
    Route::post('notifications/mark-all-read',[NotificationController::class, 'markAllRead']);
    Route::get('notifications/{id}',          [NotificationController::class, 'show']);
    Route::patch('notifications/{id}/read',   [NotificationController::class, 'markRead']);
    Route::delete('notifications/{id}',       [NotificationController::class, 'destroy']);

    // ── Templates ─────────────────────────────────────────────────────────────
    Route::get('notification-templates',               [NotificationTemplateController::class, 'index']);
    Route::post('notification-templates',              [NotificationTemplateController::class, 'store']);
    Route::get('notification-templates/{id}',          [NotificationTemplateController::class, 'show']);
    Route::put('notification-templates/{id}',          [NotificationTemplateController::class, 'update']);
    Route::patch('notification-templates/{id}/activate',   [NotificationTemplateController::class, 'activate']);
    Route::patch('notification-templates/{id}/deactivate', [NotificationTemplateController::class, 'deactivate']);
    Route::delete('notification-templates/{id}',       [NotificationTemplateController::class, 'destroy']);

    // ── User preferences ──────────────────────────────────────────────────────
    Route::get('notification-preferences',         [NotificationPreferenceController::class, 'index']);
    Route::put('notification-preferences',         [NotificationPreferenceController::class, 'upsert']);
    Route::get('notification-preferences/check',   [NotificationPreferenceController::class, 'check']);
});
