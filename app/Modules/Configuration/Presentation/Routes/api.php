<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Presentation\Controllers\ConfigurationRecordController;

Route::prefix('api/configuration')->group(function (): void {
    Route::get('{type}', [ConfigurationRecordController::class, 'index']);
    Route::get('{type}/{id}', [ConfigurationRecordController::class, 'show'])->whereNumber('id');
    Route::post('', [ConfigurationRecordController::class, 'store']);
    Route::put('{type}/{id}', [ConfigurationRecordController::class, 'update'])->whereNumber('id');
    Route::delete('{type}/{id}', [ConfigurationRecordController::class, 'destroy'])->whereNumber('id');
});
