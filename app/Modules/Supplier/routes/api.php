<?php
use Illuminate\Support\Facades\Route;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierController;
Route::prefix('api')->group(function () {
    Route::apiResource('suppliers', SupplierController::class);
});
