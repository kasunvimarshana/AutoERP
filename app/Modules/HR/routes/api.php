<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\HR\Presentation\Http\Controllers\HRRecalculationController;
use Modules\HR\Presentation\Http\Controllers\HRResourceController;

Route::prefix('api/hr')
    ->middleware('api')
    ->name('hr.')
    ->group(function (): void {
        Route::prefix('tenants/{tenant}')
            ->name('tenants.')
            ->group(function (): void {
                Route::post('leave-allocations/{allocation}/recalculate', [HRRecalculationController::class, 'leaveAllocation'])->name('leave-allocations.recalculate');
                Route::post('payslips/{payslip}/recalculate', [HRRecalculationController::class, 'payslip'])->name('payslips.recalculate');
                Route::post('payroll-runs/{payrollRun}/recalculate', [HRRecalculationController::class, 'payrollRun'])->name('payroll-runs.recalculate');

                Route::get('{resource}', [HRResourceController::class, 'index'])->name('resources.index');
                Route::post('{resource}', [HRResourceController::class, 'store'])->name('resources.store');
                Route::get('{resource}/{id}', [HRResourceController::class, 'show'])->name('resources.show');
                Route::put('{resource}/{id}', [HRResourceController::class, 'update'])->name('resources.update');
                Route::patch('{resource}/{id}', [HRResourceController::class, 'update'])->name('resources.patch');
                Route::delete('{resource}/{id}', [HRResourceController::class, 'destroy'])->name('resources.destroy');
            });
    });
