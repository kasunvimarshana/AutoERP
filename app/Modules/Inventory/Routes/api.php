<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\AdjustmentController;
use Modules\Inventory\Http\Controllers\AllocationController;
use Modules\Inventory\Http\Controllers\ReservationController;
use Modules\Inventory\Http\Controllers\StockController;
use Modules\Inventory\Http\Controllers\StockCountController;
use Modules\Inventory\Http\Controllers\TransferController;
use Modules\Inventory\Http\Controllers\ValuationController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit').':required',
    'tenant.feature:inventory',
];

Route::prefix('api/v1/inventory')->middleware($middleware)->name('api.v1.inventory.')->group(function (): void {
    Route::get('stock-balances', [StockController::class, 'balances'])->name('stock-balances.index');
    Route::get('availability', [StockController::class, 'availability'])->name('availability');
    Route::get('state-changes', [StockController::class, 'stateChanges'])->name('state-changes.index');
    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('reservations/{reservation}/release', [ReservationController::class, 'release'])->whereNumber('reservation')->name('reservations.release');
    Route::get('allocations', [AllocationController::class, 'index'])->name('allocations.index');
    Route::post('allocations', [AllocationController::class, 'store'])->name('allocations.store');
    Route::post('allocations/{allocation}/issue', [AllocationController::class, 'issue'])->whereNumber('allocation')->name('allocations.issue');
    Route::post('allocations/{allocation}/release', [AllocationController::class, 'release'])->whereNumber('allocation')->name('allocations.release');
    Route::get('adjustments', [AdjustmentController::class, 'index'])->name('adjustments.index');
    Route::post('adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
    Route::post('adjustments/{adjustment}/post', [AdjustmentController::class, 'post'])->whereNumber('adjustment')->name('adjustments.post');
    Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::post('transfers/{transfer}/post', [TransferController::class, 'post'])->whereNumber('transfer')->name('transfers.post');
    Route::post('transfers/{transfer}/receive', [TransferController::class, 'receive'])->whereNumber('transfer')->name('transfers.receive');
    Route::post('transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->whereNumber('transfer')->name('transfers.cancel');
    Route::get('valuation-layers', [ValuationController::class, 'layers'])->name('valuation-layers.index');
    Route::get('cost-adjustments', [ValuationController::class, 'adjustments'])->name('cost-adjustments.index');
    Route::post('cost-adjustments', [ValuationController::class, 'storeAdjustment'])->name('cost-adjustments.store');
    Route::post('cost-adjustments/{adjustment}/post', [ValuationController::class, 'postAdjustment'])->whereNumber('adjustment')->name('cost-adjustments.post');
    Route::get('stock-counts', [StockCountController::class, 'index'])->name('stock-counts.index');
    Route::post('stock-counts', [StockCountController::class, 'store'])->name('stock-counts.store');
    Route::post('stock-counts/{count}/approve', [StockCountController::class, 'approve'])->whereNumber('count')->name('stock-counts.approve');
    Route::post('stock-counts/{count}/post', [StockCountController::class, 'post'])->whereNumber('count')->name('stock-counts.post');
    Route::get('batches', [StockController::class, 'batches'])->name('batches.index');
    Route::get('serials', [StockController::class, 'serials'])->name('serials.index');
});
