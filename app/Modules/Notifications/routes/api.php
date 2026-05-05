<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Infrastructure\Http\Controllers\NotificationController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications', [NotificationController::class, 'store']);
    Route::get('notifications/unread', [NotificationController::class, 'unread']);
    Route::get('notifications/{id}', [NotificationController::class, 'show']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('entity-notifications/{entityType}/{entityId}', [NotificationController::class, 'byEntity']);
});
