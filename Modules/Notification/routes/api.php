<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notification\Interfaces\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Notification Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1
|
*/

Route::middleware('api')->prefix('api/v1')->name('notification.')->group(function (): void {
    Route::get('notification/templates', [NotificationController::class, 'index'])->name('templates.index');
    Route::post('notification/templates', [NotificationController::class, 'store'])->name('templates.store');
    Route::get('notification/templates/{id}', [NotificationController::class, 'show'])->name('templates.show');
    Route::put('notification/templates/{id}', [NotificationController::class, 'updateTemplate'])->name('templates.update');
    Route::delete('notification/templates/{id}', [NotificationController::class, 'destroy'])->name('templates.destroy');
    Route::post('notification/send', [NotificationController::class, 'send'])->name('send');
    Route::get('notification/logs', [NotificationController::class, 'listLogs'])->name('logs.index');
});
