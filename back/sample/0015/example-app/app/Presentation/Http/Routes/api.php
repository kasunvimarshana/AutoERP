<?php

use App\Presentation\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Context — API Routes
|--------------------------------------------------------------------------
|
| Loaded by OrderInfrastructureServiceProvider.
| All routes use the 'api' middleware group (rate limiting, JSON headers).
|
*/

Route::prefix('orders')
    ->name('orders.')
    ->middleware(['api'])
    ->group(function () {

        Route::post('/',             [OrderController::class, 'store'])->name('store');
        Route::get('/{id}',          [OrderController::class, 'show'])->name('show');
        Route::post('/{id}/cancel',  [OrderController::class, 'cancel'])->name('cancel');

    });
