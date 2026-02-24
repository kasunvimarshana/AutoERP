<?php

use Illuminate\Support\Facades\Route;
use Modules\Integration\Presentation\Controllers\ApiKeyController;
use Modules\Integration\Presentation\Controllers\WebhookController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('integration/webhooks', [WebhookController::class, 'index']);
    Route::post('integration/webhooks', [WebhookController::class, 'store']);
    Route::get('integration/webhooks/{id}', [WebhookController::class, 'show']);
    Route::put('integration/webhooks/{id}', [WebhookController::class, 'update']);
    Route::delete('integration/webhooks/{id}', [WebhookController::class, 'destroy']);

    Route::get('integration/api-keys', [ApiKeyController::class, 'index']);
    Route::post('integration/api-keys', [ApiKeyController::class, 'store']);
    Route::get('integration/api-keys/{id}', [ApiKeyController::class, 'show']);
    Route::post('integration/api-keys/{id}/revoke', [ApiKeyController::class, 'revoke']);
});
