<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Modules\User\Controllers\UserController;
use App\Http\Middleware\EnsureValidJwtFromKeycloak;
use App\Http\Middleware\CheckAbacPolicy;

/*
|--------------------------------------------------------------------------
| User Service API Routes
|--------------------------------------------------------------------------
|
*/

Route::get('/health', function (Request $request) {
    return response()->json([
        'status' => 'UP',
        'service' => 'user-service',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::middleware([EnsureValidJwtFromKeycloak::class])->group(function () {

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);

    Route::post('users', [UserController::class, 'store'])
        ->middleware(CheckAbacPolicy::class . ':can_manage_users');

    Route::put('users/{id}', [UserController::class, 'update'])
        ->middleware(CheckAbacPolicy::class . ':can_manage_users');

    Route::delete('users/{id}', [UserController::class, 'destroy'])
        ->middleware(CheckAbacPolicy::class . ':can_manage_users');
});
