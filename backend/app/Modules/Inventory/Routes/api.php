<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Inventory\Controllers\InventoryController;

Route::middleware(['auth:api', 'tenant'])->prefix('inventory')->group(function () {
    Route::get('/', [InventoryController::class, 'index']);
    Route::post('/', [InventoryController::class, 'store']);
    Route::get('/{id}', [InventoryController::class, 'show']);
    Route::put('/{id}', [InventoryController::class, 'update']);
    Route::delete('/{id}', [InventoryController::class, 'destroy']);
    Route::post('/{id}/adjust', [InventoryController::class, 'adjust']);
});
