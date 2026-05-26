<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Presentation\Http\Controllers\BatchController;
use Modules\Inventory\Presentation\Http\Controllers\SerialController;
use Modules\Inventory\Presentation\Http\Controllers\ValuationConfigController;
use Modules\Inventory\Presentation\Http\Controllers\StockLevelController;
use Modules\Inventory\Presentation\Http\Controllers\StockMovementController;
use Modules\Inventory\Presentation\Http\Controllers\InventoryCostLayerController;
use Modules\Inventory\Presentation\Http\Controllers\StockReservationController;
use Modules\Inventory\Presentation\Http\Controllers\StockTransferController;
use Modules\Inventory\Presentation\Http\Controllers\StockTransferLineController;
use Modules\Inventory\Presentation\Http\Controllers\StockAdjustmentController;
use Modules\Inventory\Presentation\Http\Controllers\StockAdjustmentLineController;
use Modules\Inventory\Presentation\Http\Controllers\CycleCountHeaderController;
use Modules\Inventory\Presentation\Http\Controllers\CycleCountLineController;
use Modules\Inventory\Presentation\Http\Controllers\TransferOrderController;
use Modules\Inventory\Presentation\Http\Controllers\TransferOrderLineController;
use Modules\Inventory\Presentation\Http\Controllers\TraceLogController;
use Modules\Inventory\Presentation\Http\Controllers\ReceiptInspectionController;
use Modules\Inventory\Presentation\Http\Controllers\PutAwayTaskController;
use Modules\Inventory\Presentation\Http\Controllers\PickingTaskController;

$protectedGuard = (string) config('module-auth.protected_route_guard', 'auth-api');
$currentUserMiddleware = (string) config('core.current_user.middleware_alias', 'current.user');
$currentTenantMiddleware = (string) config('core.current_tenant.middleware_alias', 'current.tenant');
$currentOrganizationUnitMiddleware = (string) config(
    'core.current_organization_unit.middleware_alias',
    'current.organization-unit',
);

Route::prefix('api/inventory')
    ->middleware([
        'api',
        'auth:' . $protectedGuard,
        $currentUserMiddleware,
        $currentTenantMiddleware,
        $currentOrganizationUnitMiddleware,
    ])
    ->name('inventory.')
    ->group(function (): void {
        Route::apiResource('batches', BatchController::class);
        Route::apiResource('serials', SerialController::class);
        Route::apiResource('valuation-configs', ValuationConfigController::class);
        Route::apiResource('stock-levels', StockLevelController::class);
        Route::apiResource('stock-movements', StockMovementController::class);
        Route::apiResource('inventory-cost-layers', InventoryCostLayerController::class);
        Route::apiResource('stock-reservations', StockReservationController::class);
        Route::apiResource('stock-transfers', StockTransferController::class);
        Route::apiResource('stock-transfer-lines', StockTransferLineController::class);
        Route::apiResource('stock-adjustments', StockAdjustmentController::class);
        Route::apiResource('stock-adjustment-lines', StockAdjustmentLineController::class);
        Route::apiResource('cycle-count-headers', CycleCountHeaderController::class);
        Route::apiResource('cycle-count-lines', CycleCountLineController::class);
        Route::apiResource('transfer-orders', TransferOrderController::class);
        Route::apiResource('transfer-order-lines', TransferOrderLineController::class);
        Route::apiResource('trace-logs', TraceLogController::class);
        Route::apiResource('receipt-inspections', ReceiptInspectionController::class);
        Route::apiResource('put-away-tasks', PutAwayTaskController::class);
        Route::apiResource('picking-tasks', PickingTaskController::class);
    });