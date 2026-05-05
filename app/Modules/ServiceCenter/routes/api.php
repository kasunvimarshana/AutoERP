<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ServiceCenter\Infrastructure\Http\Controllers\ServiceOrderController;

Route::middleware(['auth.configured', 'resolve.tenant'])->prefix('service-orders')->group(function (): void {
    Route::get('/', [ServiceOrderController::class, 'index']);
    Route::post('/', [ServiceOrderController::class, 'store']);
    Route::get('/{id}', [ServiceOrderController::class, 'show']);
    Route::post('/{id}/complete', [ServiceOrderController::class, 'complete']);
    Route::post('/{id}/cancel', [ServiceOrderController::class, 'cancel']);
    Route::get('/{id}/tasks', [ServiceOrderController::class, 'tasks']);
    Route::get('/{id}/parts', [ServiceOrderController::class, 'parts']);
});
