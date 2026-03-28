<?php

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::apiResource('suppliers', SupplierController::class);
});
