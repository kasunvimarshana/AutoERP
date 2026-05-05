<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Tax\Infrastructure\Http\Controllers\TaxCalculationController;
use Modules\Tax\Infrastructure\Http\Controllers\TaxGroupController;
use Modules\Tax\Infrastructure\Http\Controllers\TaxRateController;
use Modules\Tax\Infrastructure\Http\Controllers\TaxRuleController;

Route::middleware(['auth.configured', 'resolve.tenant'])->group(function (): void {
    Route::get('tax/groups', [TaxGroupController::class, 'index']);
    Route::post('tax/groups', [TaxGroupController::class, 'store']);
    Route::get('tax/groups/{taxGroup}', [TaxGroupController::class, 'show']);
    Route::put('tax/groups/{taxGroup}', [TaxGroupController::class, 'update']);
    Route::delete('tax/groups/{taxGroup}', [TaxGroupController::class, 'destroy']);

    Route::get('tax/groups/{taxGroup}/rates', [TaxRateController::class, 'index']);
    Route::post('tax/groups/{taxGroup}/rates', [TaxRateController::class, 'store']);
    Route::get('tax/groups/{taxGroup}/rates/{taxRate}', [TaxRateController::class, 'show']);
    Route::put('tax/groups/{taxGroup}/rates/{taxRate}', [TaxRateController::class, 'update']);
    Route::delete('tax/groups/{taxGroup}/rates/{taxRate}', [TaxRateController::class, 'destroy']);

    Route::get('tax/groups/{taxGroup}/rules', [TaxRuleController::class, 'index']);
    Route::post('tax/groups/{taxGroup}/rules', [TaxRuleController::class, 'store']);
    Route::get('tax/groups/{taxGroup}/rules/{taxRule}', [TaxRuleController::class, 'show']);
    Route::put('tax/groups/{taxGroup}/rules/{taxRule}', [TaxRuleController::class, 'update']);
    Route::delete('tax/groups/{taxGroup}/rules/{taxRule}', [TaxRuleController::class, 'destroy']);

    Route::post('tax/resolve', [TaxCalculationController::class, 'resolve']);
    Route::post('tax/transactions/{referenceType}/{referenceId}/lines', [TaxCalculationController::class, 'record']);
    Route::get('tax/transactions/{referenceType}/{referenceId}/lines', [TaxCalculationController::class, 'index']);
});
