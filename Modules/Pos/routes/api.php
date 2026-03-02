<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\POS\Interfaces\Http\Controllers\POSController;

/*
|--------------------------------------------------------------------------
| POS Module API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1
|
*/

Route::middleware('auth:api')->prefix('api/v1')->name('pos.')->group(function (): void {
    Route::post('pos/transactions', [POSController::class, 'createTransaction'])->name('transactions.store');
    Route::get('pos/transactions/{id}', [POSController::class, 'showTransaction'])->name('transactions.show');
    Route::post('pos/transactions/{id}/void', [POSController::class, 'voidTransaction'])->name('transactions.void');
    Route::post('pos/sync', [POSController::class, 'syncOfflineTransactions'])->name('sync');
    Route::get('pos/sessions', [POSController::class, 'listSessions'])->name('sessions.index');
    Route::post('pos/sessions', [POSController::class, 'openSession'])->name('sessions.store');
    Route::post('pos/sessions/{id}/close', [POSController::class, 'closeSession'])->name('sessions.close');
});
