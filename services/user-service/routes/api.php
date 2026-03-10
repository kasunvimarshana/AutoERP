<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — User Service
|--------------------------------------------------------------------------
*/

Route::get('/health', HealthController::class)->name('health');

Route::middleware(['resolve.tenant'])->group(function (): void {

    Route::middleware('auth:api')->group(function (): void {

        Route::prefix('users')->name('users.')->group(function (): void {
            Route::get('/',                       [UserController::class, 'index'])->name('index');
            Route::get('/{id}',                   [UserController::class, 'show'])->name('show');
            Route::put('/{id}',                   [UserController::class, 'update'])->name('update');
            Route::delete('/{id}',                [UserController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/preferences',     [UserController::class, 'updatePreferences'])->name('preferences');
            Route::post('/{id}/roles',            [UserController::class, 'assignRole'])->name('assignRole');
        });
    });
});
