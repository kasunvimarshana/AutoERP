<?php
use Illuminate\Support\Facades\Route;
use Modules\User\Presentation\Controllers\UserController;
use Modules\User\Presentation\Controllers\RoleController;
Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::post('users/{id}/invite', [UserController::class, 'invite']);
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{id}/assign/{userId}', [RoleController::class, 'assign']);
});
