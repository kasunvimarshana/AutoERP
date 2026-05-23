<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Finance\Presentation\Http\Controllers\FinanceJournalEntryController;
use Modules\Finance\Presentation\Http\Controllers\FinanceRecalculationController;
use Modules\Finance\Presentation\Http\Controllers\FinanceResourceController;

Route::prefix('api/finance')
    ->middleware('api')
    ->name('finance.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('journal-entries/{journalEntry}/post', [FinanceJournalEntryController::class, 'post'])
                    ->name('journal-entries.post');

                Route::post('bank-accounts/{bankAccount}/recalculate-balance', [FinanceRecalculationController::class, 'bankAccountBalance'])
                    ->name('bank-accounts.recalculate-balance');

                Route::get('{resource}', [FinanceResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [FinanceResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [FinanceResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [FinanceResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [FinanceResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [FinanceResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
