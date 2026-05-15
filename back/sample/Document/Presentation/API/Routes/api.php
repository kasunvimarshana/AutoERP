<?php

use Illuminate\Support\Facades\Route;
use Modules\Document\Presentation\API\Controllers\DocumentController;

Route::prefix('api')->group(function (): void {
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::patch('documents/{document}/status', [DocumentController::class, 'changeStatus'])->name('documents.change-status');
    Route::post('documents/{document}/attachments', [DocumentController::class, 'uploadAttachment'])->name('documents.attachments.store');
});
