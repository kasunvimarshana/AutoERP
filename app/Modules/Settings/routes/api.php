<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Settings\Infrastructure\Http\Controllers\SettingController;

Route::prefix('settings')->group(function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::post('/', [SettingController::class, 'store']);
    Route::get('/{id}', [SettingController::class, 'show']);
    Route::put('/{id}', [SettingController::class, 'update']);
    Route::delete('/{id}', [SettingController::class, 'destroy']);
});
