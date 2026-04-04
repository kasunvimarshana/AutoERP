<?php

use Illuminate\Support\Facades\Route;
use Modules\Attachment\Infrastructure\Http\Controllers\AttachmentController;

Route::middleware('auth:sanctum')->prefix('attachments')->group(function () {
    Route::get('/', [AttachmentController::class, 'index']);
    Route::post('/', [AttachmentController::class, 'store']);
    Route::delete('/{id}', [AttachmentController::class, 'destroy']);
});
