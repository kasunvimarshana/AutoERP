<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Infrastructure\Http\Controllers\SupplierController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::apiResource('suppliers', SupplierController::class);
});
