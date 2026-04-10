<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Inventory\Http\Controllers\InventoryController;

Route::prefix('inventory')->group(function () {
    Route::post('/adjust', [InventoryController::class, 'adjust']);
});