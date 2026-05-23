<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payment\Presentation\Http\Controllers\PaymentLifecycleController;
use Modules\Payment\Presentation\Http\Controllers\PaymentResourceController;

Route::prefix('api/payment')
    ->middleware('api')
    ->name('payment.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('payments/{payment}/post', [PaymentLifecycleController::class, 'post'])->name('payments.post');
                Route::post('advance-payments/{advancePayment}/recalculate', [PaymentLifecycleController::class, 'recalculateAdvance'])->name('advance-payments.recalculate');

                Route::get('{resource}', [PaymentResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [PaymentResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [PaymentResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [PaymentResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [PaymentResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [PaymentResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
