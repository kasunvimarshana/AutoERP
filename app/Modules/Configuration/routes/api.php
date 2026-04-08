<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Infrastructure\Http\Controllers\OrgUnitController;
use Modules\Configuration\Infrastructure\Http\Controllers\SettingController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::apiResource('settings', SettingController::class);
    Route::apiResource('org-units', OrgUnitController::class);
    Route::get('org-units/{orgUnit}/tree', [OrgUnitController::class, 'tree']);
    Route::get('org-units/{orgUnit}/descendants', [OrgUnitController::class, 'descendants']);
    Route::get('org-units/{orgUnit}/ancestors', [OrgUnitController::class, 'ancestors']);
    Route::post('org-units/{orgUnit}/move', [OrgUnitController::class, 'move']);
});
