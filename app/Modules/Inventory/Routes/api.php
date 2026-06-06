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
    Route::post('reservations', [InventoryController::class, 'reserve'])->name('reservations.store');
    Route::post('reservations/{reservation}/release', [InventoryController::class, 'releaseReservation'])->whereNumber('reservation')->name('reservations.release');
    Route::post('allocations', [InventoryController::class, 'allocate'])->name('allocations.store');
    Route::post('allocations/{allocation}/release', [InventoryController::class, 'releaseAllocation'])->whereNumber('allocation')->name('allocations.release');
    Route::post('adjustments', [InventoryController::class, 'createAdjustment'])->name('adjustments.store');
    Route::post('adjustments/{adjustment}/post', [InventoryController::class, 'postAdjustment'])->whereNumber('adjustment')->name('adjustments.post');
    Route::post('transfers', [InventoryController::class, 'createTransfer'])->name('transfers.store');
    Route::post('transfers/{transfer}/post', [InventoryController::class, 'postTransfer'])->whereNumber('transfer')->name('transfers.post');
    Route::get('batches', [InventoryController::class, 'batches'])->name('batches.index');
    Route::get('serials', [InventoryController::class, 'serials'])->name('serials.index');
});
