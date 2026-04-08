<?php

use Illuminate\Support\Facades\Route;
use Modules\Account\Infrastructure\Http\Controllers\AccountController;

Route::middleware(['auth:api', 'resolve.tenant'])->group(function () {
    Route::apiResource('accounts', AccountController::class);
});
