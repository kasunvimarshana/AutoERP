<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Constants\InventoryPermission;
use Modules\Inventory\Http\Controllers\AdjustmentController;
use Modules\Inventory\Http\Controllers\AllocationController;
use Modules\Inventory\Http\Controllers\OpeningStockImportController;
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
$permissionMiddleware = (string) config('user.tenant.permission_middleware_alias', 'tenant.permission');
$requires = static fn (string $permission): string => $permissionMiddleware.':'.$permission;

Route::prefix('api/v1/inventory')->middleware($middleware)->name('api.v1.inventory.')->group(function () use ($requires): void {
    Route::get('stock-balances', [StockController::class, 'balances'])
        ->middleware($requires(InventoryPermission::STOCK_VIEW))
        ->name('stock-balances.index');
    Route::get('availability', [StockController::class, 'availability'])
        ->middleware($requires(InventoryPermission::STOCK_VIEW))
        ->name('availability');
    Route::get('state-changes', [StockController::class, 'stateChanges'])
        ->middleware($requires(InventoryPermission::AUDIT_VIEW))
        ->name('state-changes.index');

    Route::get('reservations', [ReservationController::class, 'index'])
        ->middleware($requires(InventoryPermission::RESERVATIONS_VIEW))
        ->name('reservations.index');
    Route::post('reservations', [ReservationController::class, 'store'])
        ->middleware($requires(InventoryPermission::RESERVATIONS_MANAGE))
        ->name('reservations.store');
    Route::post('reservations/{reservation}/release', [ReservationController::class, 'release'])
        ->whereNumber('reservation')
        ->middleware($requires(InventoryPermission::RESERVATIONS_MANAGE))
        ->name('reservations.release');

    Route::get('allocations', [AllocationController::class, 'index'])
        ->middleware($requires(InventoryPermission::ALLOCATIONS_VIEW))
        ->name('allocations.index');
    Route::post('allocations', [AllocationController::class, 'store'])
        ->middleware($requires(InventoryPermission::ALLOCATIONS_MANAGE))
        ->name('allocations.store');
    Route::post('allocations/{allocation}/issue', [AllocationController::class, 'issue'])
        ->whereNumber('allocation')
        ->middleware($requires(InventoryPermission::ALLOCATIONS_ISSUE))
        ->name('allocations.issue');
    Route::post('allocations/{allocation}/release', [AllocationController::class, 'release'])
        ->whereNumber('allocation')
        ->middleware($requires(InventoryPermission::ALLOCATIONS_MANAGE))
        ->name('allocations.release');

    Route::get('adjustments', [AdjustmentController::class, 'index'])
        ->middleware($requires(InventoryPermission::ADJUSTMENTS_VIEW))
        ->name('adjustments.index');
    Route::post('adjustments', [AdjustmentController::class, 'store'])
        ->middleware($requires(InventoryPermission::ADJUSTMENTS_MANAGE))
        ->name('adjustments.store');
    Route::get('opening-stock-import/template', [OpeningStockImportController::class, 'template'])
        ->middleware($requires(InventoryPermission::ADJUSTMENTS_MANAGE))
        ->name('opening-stock-import.template');
    Route::post('opening-stock-import/preview', [OpeningStockImportController::class, 'preview'])
        ->middleware($requires(InventoryPermission::ADJUSTMENTS_MANAGE))
        ->name('opening-stock-import.preview');
    Route::post('opening-stock-import', [OpeningStockImportController::class, 'store'])
        ->middleware($requires(InventoryPermission::ADJUSTMENTS_MANAGE))
        ->name('opening-stock-import.store');
    Route::post('adjustments/{adjustment}/post', [AdjustmentController::class, 'post'])
        ->whereNumber('adjustment')
        ->middleware($requires(InventoryPermission::ADJUSTMENTS_POST))
        ->name('adjustments.post');

    Route::get('transfers', [TransferController::class, 'index'])
        ->middleware($requires(InventoryPermission::TRANSFERS_VIEW))
        ->name('transfers.index');
    Route::post('transfers', [TransferController::class, 'store'])
        ->middleware($requires(InventoryPermission::TRANSFERS_MANAGE))
        ->name('transfers.store');
    Route::post('transfers/{transfer}/post', [TransferController::class, 'post'])
        ->whereNumber('transfer')
        ->middleware($requires(InventoryPermission::TRANSFERS_DISPATCH))
        ->name('transfers.post');
    Route::post('transfers/{transfer}/receive', [TransferController::class, 'receive'])
        ->whereNumber('transfer')
        ->middleware($requires(InventoryPermission::TRANSFERS_RECEIVE))
        ->name('transfers.receive');
    Route::post('transfers/{transfer}/cancel', [TransferController::class, 'cancel'])
        ->whereNumber('transfer')
        ->middleware($requires(InventoryPermission::TRANSFERS_MANAGE))
        ->name('transfers.cancel');

    Route::get('valuation-layers', [ValuationController::class, 'layers'])
        ->middleware($requires(InventoryPermission::VALUATION_VIEW))
        ->name('valuation-layers.index');
    Route::get('cost-adjustments', [ValuationController::class, 'adjustments'])
        ->middleware($requires(InventoryPermission::COST_ADJUSTMENTS_VIEW))
        ->name('cost-adjustments.index');
    Route::post('cost-adjustments', [ValuationController::class, 'storeAdjustment'])
        ->middleware($requires(InventoryPermission::COST_ADJUSTMENTS_MANAGE))
        ->name('cost-adjustments.store');
    Route::post('cost-adjustments/{adjustment}/post', [ValuationController::class, 'postAdjustment'])
        ->whereNumber('adjustment')
        ->middleware($requires(InventoryPermission::COST_ADJUSTMENTS_POST))
        ->name('cost-adjustments.post');

    Route::get('stock-counts', [StockCountController::class, 'index'])
        ->middleware($requires(InventoryPermission::STOCK_COUNTS_VIEW))
        ->name('stock-counts.index');
    Route::post('stock-counts', [StockCountController::class, 'store'])
        ->middleware($requires(InventoryPermission::STOCK_COUNTS_MANAGE))
        ->name('stock-counts.store');
    Route::post('stock-counts/{count}/approve', [StockCountController::class, 'approve'])
        ->whereNumber('count')
        ->middleware($requires(InventoryPermission::STOCK_COUNTS_APPROVE))
        ->name('stock-counts.approve');
    Route::post('stock-counts/{count}/post', [StockCountController::class, 'post'])
        ->whereNumber('count')
        ->middleware($requires(InventoryPermission::STOCK_COUNTS_POST))
        ->name('stock-counts.post');

    Route::get('batches', [StockController::class, 'batches'])
        ->middleware($requires(InventoryPermission::TRACKING_VIEW))
        ->name('batches.index');
    Route::get('serials', [StockController::class, 'serials'])
        ->middleware($requires(InventoryPermission::TRACKING_VIEW))
        ->name('serials.index');
});
