<?php
use Illuminate\Support\Facades\Route;
use Modules\Media\Presentation\Controllers\MediaController;
Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('media', [MediaController::class, 'index']);
    Route::post('media/upload', [MediaController::class, 'upload']);
    Route::get('media/{id}', [MediaController::class, 'show']);
    Route::delete('media/{id}', [MediaController::class, 'destroy']);
    Route::get('media/{id}/temporary-url', [MediaController::class, 'temporaryUrl']);
});
