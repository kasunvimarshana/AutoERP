<?php
use Illuminate\Support\Facades\Route;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryBatchController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryCycleCountController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryLevelController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventorySerialController;
use Modules\Inventory\Infrastructure\Http\Controllers\InventorySettingController;

Route::prefix('inventory')->group(function () {
    Route::get('/levels',              [InventoryLevelController::class, 'index']);
    Route::get('/levels/{id}',         [InventoryLevelController::class, 'show']);
    Route::post('/levels/{id}/reserve',[InventoryLevelController::class, 'reserve']);
    Route::post('/levels/{id}/release',[InventoryLevelController::class, 'release']);
    Route::post('/levels/{id}/adjust', [InventoryLevelController::class, 'adjust']);

    Route::get('/batches',             [InventoryBatchController::class, 'index']);
    Route::post('/batches',            [InventoryBatchController::class, 'store']);
    Route::get('/batches/{id}',        [InventoryBatchController::class, 'show']);

    Route::get('/serials',             [InventorySerialController::class, 'index']);
    Route::post('/serials',            [InventorySerialController::class, 'store']);
    Route::get('/serials/{id}',        [InventorySerialController::class, 'show']);

    Route::get('/settings/{tenantId}',   [InventorySettingController::class, 'show']);
    Route::patch('/settings/{tenantId}', [InventorySettingController::class, 'update']);

    Route::get('/cycle-counts',                    [InventoryCycleCountController::class, 'index']);
    Route::post('/cycle-counts',                   [InventoryCycleCountController::class, 'store']);
    Route::post('/cycle-counts/{id}/reconcile',    [InventoryCycleCountController::class, 'reconcile']);
});
