<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerController;

Route::prefix('api')->middleware(['auth:api'])->group(function (): void {
    Route::apiResource('customers', CustomerController::class);
});
