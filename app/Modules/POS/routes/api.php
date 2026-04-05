<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::prefix('pos')->group(function () {
        // Terminals
        Route::get('/terminals',                   [\Modules\POS\Infrastructure\Http\Controllers\PosTerminalController::class, 'index']);
        Route::post('/terminals',                  [\Modules\POS\Infrastructure\Http\Controllers\PosTerminalController::class, 'store']);
        Route::get('/terminals/{id}',              [\Modules\POS\Infrastructure\Http\Controllers\PosTerminalController::class, 'show']);
        Route::put('/terminals/{id}',              [\Modules\POS\Infrastructure\Http\Controllers\PosTerminalController::class, 'update']);
        Route::delete('/terminals/{id}',           [\Modules\POS\Infrastructure\Http\Controllers\PosTerminalController::class, 'destroy']);

        // Sessions
        Route::post('/sessions/open',              [\Modules\POS\Infrastructure\Http\Controllers\PosSessionController::class, 'open']);
        Route::post('/sessions/{id}/close',        [\Modules\POS\Infrastructure\Http\Controllers\PosSessionController::class, 'close']);
        Route::get('/sessions',                    [\Modules\POS\Infrastructure\Http\Controllers\PosSessionController::class, 'index']);
        Route::get('/sessions/{id}',               [\Modules\POS\Infrastructure\Http\Controllers\PosSessionController::class, 'show']);

        // Transactions
        Route::post('/transactions',               [\Modules\POS\Infrastructure\Http\Controllers\PosTransactionController::class, 'store']);
        Route::get('/transactions/{id}',           [\Modules\POS\Infrastructure\Http\Controllers\PosTransactionController::class, 'show']);
        Route::post('/transactions/{id}/void',     [\Modules\POS\Infrastructure\Http\Controllers\PosTransactionController::class, 'void']);
    });
});
