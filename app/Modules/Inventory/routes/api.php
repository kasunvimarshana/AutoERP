<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Infrastructure\Http\Controllers\InventoryController;

Route::prefix('api')->group(function () {
    Route::get('inventory/levels', [InventoryController::class, 'levels']);
    Route::post('inventory/receive', [InventoryController::class, 'receive']);
    Route::post('inventory/issue', [InventoryController::class, 'issue']);
    Route::post('inventory/adjust', [InventoryController::class, 'adjust']);
    Route::post('inventory/reserve', [InventoryController::class, 'reserve']);
    Route::post('inventory/release', [InventoryController::class, 'release']);
});
