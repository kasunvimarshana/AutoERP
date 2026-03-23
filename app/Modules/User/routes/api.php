<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Infrastructure\Http\Controllers\UserController;
use Modules\User\Infrastructure\Http\Controllers\UserAttachmentController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/assign-role', [UserController::class, 'assignRole']);
    Route::patch('users/{user}/preferences', [UserController::class, 'updatePreferences']);

    Route::get('users/{user}/attachments', [UserAttachmentController::class, 'index']);
    Route::post('users/{user}/attachments', [UserAttachmentController::class, 'store']);
    Route::delete('users/{user}/attachments/{attachment}', [UserAttachmentController::class, 'destroy']);
});

// File serving (authenticated)
Route::get('storage/user-attachments/{uuid}', [UserAttachmentController::class, 'serve'])->middleware('auth:api');

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
