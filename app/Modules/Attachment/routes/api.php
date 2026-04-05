<?php
use Illuminate\Support\Facades\Route;
use Modules\Attachment\Infrastructure\Http\Controllers\AttachmentController;
Route::prefix('api')->group(function () {
    Route::get('attachments', [AttachmentController::class, 'index']);
    Route::post('attachments', [AttachmentController::class, 'store']);
    Route::delete('attachments/{id}', [AttachmentController::class, 'destroy']);
});
