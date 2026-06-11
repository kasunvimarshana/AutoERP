<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;

$middleware = [
    'api',
    'auth:'.(string) config('module-auth.protected_route_guard', 'auth-api'),
    (string) config('core.current_user.middleware_alias', 'current.user'),
    (string) config('core.current_tenant.middleware_alias', 'current.tenant'),
    (string) config('core.current_organization_unit.middleware_alias', 'current.organization-unit'),
];

Route::prefix('api/v1/inventory')->middleware($middleware)->name('api.v1.inventory.')->group(function (): void {
    Route::get('stock-balances', [InventoryController::class, 'stockBalances'])->name('stock-balances.index');
    Route::get('availability', [InventoryController::class, 'availability'])->name('availability');
    Route::get('state-changes', [InventoryController::class, 'stateChanges'])->name('state-changes.index');
    Route::get('reservations', [InventoryController::class, 'reservations'])->name('reservations.index');
    Route::post('reservations', [InventoryController::class, 'reserve'])->name('reservations.store');
    Route::post('reservations/{reservation}/release', [InventoryController::class, 'releaseReservation'])->whereNumber('reservation')->name('reservations.release');
    Route::get('allocations', [InventoryController::class, 'allocations'])->name('allocations.index');
    Route::post('allocations', [InventoryController::class, 'allocate'])->name('allocations.store');
    Route::post('allocations/{allocation}/issue', [InventoryController::class, 'issueAllocation'])->whereNumber('allocation')->name('allocations.issue');
    Route::post('allocations/{allocation}/release', [InventoryController::class, 'releaseAllocation'])->whereNumber('allocation')->name('allocations.release');
    Route::get('adjustments', [InventoryController::class, 'adjustments'])->name('adjustments.index');
    Route::post('adjustments', [InventoryController::class, 'createAdjustment'])->name('adjustments.store');
    Route::post('adjustments/{adjustment}/post', [InventoryController::class, 'postAdjustment'])->whereNumber('adjustment')->name('adjustments.post');
    Route::get('transfers', [InventoryController::class, 'transfers'])->name('transfers.index');
    Route::post('transfers', [InventoryController::class, 'createTransfer'])->name('transfers.store');
    Route::post('transfers/{transfer}/post', [InventoryController::class, 'postTransfer'])->whereNumber('transfer')->name('transfers.post');
    Route::post('transfers/{transfer}/receive', [InventoryController::class, 'receiveTransfer'])->whereNumber('transfer')->name('transfers.receive');
    Route::post('transfers/{transfer}/cancel', [InventoryController::class, 'cancelTransfer'])->whereNumber('transfer')->name('transfers.cancel');
    Route::get('valuation-layers', [InventoryController::class, 'valuationLayers'])->name('valuation-layers.index');
    Route::get('cost-adjustments', [InventoryController::class, 'costAdjustments'])->name('cost-adjustments.index');
    Route::post('cost-adjustments', [InventoryController::class, 'createCostAdjustment'])->name('cost-adjustments.store');
    Route::post('cost-adjustments/{adjustment}/post', [InventoryController::class, 'postCostAdjustment'])->whereNumber('adjustment')->name('cost-adjustments.post');
    Route::get('stock-counts', [InventoryController::class, 'stockCounts'])->name('stock-counts.index');
    Route::post('stock-counts', [InventoryController::class, 'createStockCount'])->name('stock-counts.store');
    Route::post('stock-counts/{count}/approve', [InventoryController::class, 'approveStockCount'])->whereNumber('count')->name('stock-counts.approve');
    Route::post('stock-counts/{count}/post', [InventoryController::class, 'postStockCount'])->whereNumber('count')->name('stock-counts.post');
    Route::get('batches', [InventoryController::class, 'batches'])->name('batches.index');
    Route::get('serials', [InventoryController::class, 'serials'])->name('serials.index');
});
