<?php

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Infrastructure\Http\Controllers\OrgUnitController;
use Modules\Configuration\Infrastructure\Http\Controllers\SettingController;

Route::prefix('api')->group(function () {
    Route::get('settings', [SettingController::class, 'index']);
    Route::post('settings', [SettingController::class, 'store']);
    Route::get('settings/{key}', [SettingController::class, 'show']);
    Route::delete('settings/{key}', [SettingController::class, 'destroy']);
    Route::get('org-units/tree', [OrgUnitController::class, 'tree']);
    Route::apiResource('org-units', OrgUnitController::class);
});
