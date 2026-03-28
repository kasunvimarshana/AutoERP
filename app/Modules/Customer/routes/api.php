<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::apiResource('customers', CustomerController::class);
});
