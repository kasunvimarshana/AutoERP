<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show']);
    Route::post('/notifications/{id}/retry', [NotificationController::class, 'retry']);
});

Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'notification-service']));
