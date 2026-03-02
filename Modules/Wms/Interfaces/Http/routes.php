<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Wms\Interfaces\Http\Controllers\AisleController;
use Modules\Wms\Interfaces\Http\Controllers\BinController;
use Modules\Wms\Interfaces\Http\Controllers\CycleCountController;
use Modules\Wms\Interfaces\Http\Controllers\ZoneController;

Route::prefix('api/v1/wms')->group(function (): void {
    // Zones
    Route::get('/zones', [ZoneController::class, 'index']);
    Route::post('/zones', [ZoneController::class, 'store']);
    Route::get('/zones/{id}', [ZoneController::class, 'show']);
    Route::put('/zones/{id}', [ZoneController::class, 'update']);
    Route::delete('/zones/{id}', [ZoneController::class, 'destroy']);
    Route::get('/zones/{id}/aisles', [ZoneController::class, 'aisles']);

    // Aisles
    Route::post('/aisles', [AisleController::class, 'store']);
    Route::get('/aisles/{id}', [AisleController::class, 'show']);
    Route::put('/aisles/{id}', [AisleController::class, 'update']);
    Route::delete('/aisles/{id}', [AisleController::class, 'destroy']);
    Route::get('/aisles/{id}/bins', [AisleController::class, 'bins']);

    // Bins
    Route::post('/bins', [BinController::class, 'store']);
    Route::get('/bins/{id}', [BinController::class, 'show']);
    Route::put('/bins/{id}', [BinController::class, 'update']);
    Route::delete('/bins/{id}', [BinController::class, 'destroy']);

    // Cycle Counts
    Route::get('/cycle-counts', [CycleCountController::class, 'index']);
    Route::post('/cycle-counts', [CycleCountController::class, 'store']);
    Route::get('/cycle-counts/{id}', [CycleCountController::class, 'show']);
    Route::delete('/cycle-counts/{id}', [CycleCountController::class, 'destroy']);
    Route::post('/cycle-counts/{id}/start', [CycleCountController::class, 'start']);
    Route::post('/cycle-counts/{id}/lines', [CycleCountController::class, 'recordLine']);
    Route::get('/cycle-counts/{id}/lines', [CycleCountController::class, 'lines']);
    Route::post('/cycle-counts/{id}/complete', [CycleCountController::class, 'complete']);
});
