<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Integration\Interfaces\Http\Controllers\IntegrationController;

/*
|--------------------------------------------------------------------------
| Integration Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1
|
*/

Route::middleware('api')->prefix('api/v1')->name('integration.')->group(function (): void {
    Route::get('integration/webhooks', [IntegrationController::class, 'index'])->name('webhooks.index');
    Route::post('integration/webhooks', [IntegrationController::class, 'store'])->name('webhooks.store');
    Route::get('integration/webhooks/{id}', [IntegrationController::class, 'showWebhook'])->name('webhooks.show');
    Route::post('integration/webhooks/{id}/dispatch', [IntegrationController::class, 'dispatch'])->name('webhooks.dispatch');
    Route::get('integration/logs', [IntegrationController::class, 'logs'])->name('logs.index');
    Route::get('integration/deliveries', [IntegrationController::class, 'listDeliveries'])->name('deliveries.index');
});
