<?php
use Illuminate\Support\Facades\Route;
use Modules\Setting\Presentation\Controllers\SettingController;
Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('settings/group/{group}', [SettingController::class, 'group']);
    Route::get('settings/{key}', [SettingController::class, 'show']);
    Route::put('settings/{key}', [SettingController::class, 'update']);
});
