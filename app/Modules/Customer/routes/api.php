<?php
use Illuminate\Support\Facades\Route;
use Modules\Customer\Infrastructure\Http\Controllers\CustomerController;
Route::prefix('api')->group(function () {
    Route::apiResource('customers', CustomerController::class);
});
