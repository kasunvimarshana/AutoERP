<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Voucher\Presentation\Http\Controllers\VoucherLifecycleController;
use Modules\Voucher\Presentation\Http\Controllers\VoucherResourceController;

Route::prefix('api/voucher')
    ->middleware('api')
    ->name('voucher.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('vouchers/{voucher}/post', [VoucherLifecycleController::class, 'post'])->name('vouchers.post');
                Route::post('recurring-vouchers/{recurringVoucher}/generate', [VoucherLifecycleController::class, 'generate'])->name('recurring-vouchers.generate');

                Route::get('{resource}', [VoucherResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [VoucherResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [VoucherResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [VoucherResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [VoucherResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [VoucherResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
