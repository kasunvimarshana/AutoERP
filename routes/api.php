<?php

use App\Http\Controllers\SystemUserController;
use Illuminate\Support\Facades\Route;

Route::middleware([])->group(function () {
    // System User
    Route::post('system-users', [SystemUserController::class, 'store']);
    Route::get('system-users', [SystemUserController::class, 'index']);
    Route::get('system-users/{system_user}', [SystemUserController::class, 'show']);
    Route::put('system-users/{system_user}', [SystemUserController::class, 'update']);
    Route::delete('system-users/{system_user}', [SystemUserController::class, 'delete']);
});
