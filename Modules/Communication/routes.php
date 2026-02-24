<?php

use Illuminate\Support\Facades\Route;
use Modules\Communication\Presentation\Controllers\CommunicationController;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('communication/channels', [CommunicationController::class, 'indexChannels']);
    Route::post('communication/channels', [CommunicationController::class, 'storeChannel']);
    Route::get('communication/channels/{id}', [CommunicationController::class, 'showChannel']);
    Route::delete('communication/channels/{id}', [CommunicationController::class, 'destroyChannel']);

    Route::get('communication/channels/{channelId}/messages', [CommunicationController::class, 'indexMessages']);
    Route::post('communication/channels/{channelId}/messages', [CommunicationController::class, 'sendMessage']);
});
