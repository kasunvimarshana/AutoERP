<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    ProductController,
    ProductVariantController,
    InventoryController,
    BatchController,
    LotController,
    SerialNumberController,
    PurchaseOrderController,
    GoodsReceiptController,
    SalesOrderController,
    StockTransferController,
    StockAdjustmentController,
    PhysicalCountController,
    WarehouseController,
    ReportController,
    AuditLogController,
    ReorderRuleController,
    SupplierController,
    CustomerController,
    AllocationController,
};

/*
|--------------------------------------------------------------------------
| Inventory Management System — API Routes
|--------------------------------------------------------------------------
|
| All routes are versioned under /api/v1
| Authentication: Laravel Sanctum (token or session)
| Middleware: auth:sanctum + org scoping
|
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'scope.organization'])->group(function () {

    // ── Products & Catalog ────────────────────────────────────────────────────
    Route::apiResource('products', ProductController::class);
    Route::apiResource('products.variants', ProductVariantController::class)->shallow();
    Route::get('products/{product}/stock', [ProductController::class, 'stock']);
    Route::get('products/{product}/movements', [ProductController::class, 'movements']);
    Route::get('products/{product}/valuations', [ProductController::class, 'valuations']);

    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('warehouses', WarehouseController::class);

    // ── Batch / Lot / Serial Number Tracking ─────────────────────────────────
    Route::apiResource('batches', BatchController::class);
    Route::get('batches/{batch}/trace', [BatchController::class, 'trace']);        // Full forward/backward trace
    Route::post('batches/{batch}/qc-approve', [BatchController::class, 'qcApprove']);
    Route::post('batches/{batch}/qc-reject',  [BatchController::class, 'qcReject']);
    Route::post('batches/{batch}/hold',        [BatchController::class, 'hold']);
    Route::post('batches/{batch}/release',     [BatchController::class, 'release']);

    Route::apiResource('lots', LotController::class);
    Route::get('lots/{lot}/movements', [LotController::class, 'movements']);

    Route::apiResource('serial-numbers', SerialNumberController::class);
    Route::get('serial-numbers/{serial}/history', [SerialNumberController::class, 'history']);

    // ── Inventory (stock positions, movements, adjustments) ───────────────────
    Route::prefix('inventory')->group(function () {
        Route::get('positions',            [InventoryController::class, 'positions']);    // current stock
        Route::get('positions/{product}',  [InventoryController::class, 'productStock']);
        Route::get('ledger',               [InventoryController::class, 'ledger']);       // movement journal
        Route::post('adjustments',         [InventoryController::class, 'adjust']);       // manual adjustment
        Route::get('costing-layers',       [InventoryController::class, 'costingLayers']);
        Route::get('alerts',               [InventoryController::class, 'alerts']);
        Route::post('alerts/{alert}/acknowledge', [InventoryController::class, 'acknowledgeAlert']);
    });

    // ── Stock Transfers ───────────────────────────────────────────────────────
    Route::apiResource('transfers', StockTransferController::class);
    Route::post('transfers/{transfer}/approve',  [StockTransferController::class, 'approve']);
    Route::post('transfers/{transfer}/ship',     [StockTransferController::class, 'ship']);
    Route::post('transfers/{transfer}/receive',  [StockTransferController::class, 'receive']);
    Route::post('transfers/{transfer}/cancel',   [StockTransferController::class, 'cancel']);

    // ── Stock Adjustments ─────────────────────────────────────────────────────
    Route::apiResource('adjustments', StockAdjustmentController::class);
    Route::post('adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve']);
    Route::post('adjustments/{adjustment}/post',    [StockAdjustmentController::class, 'post']);

    // ── Purchase Orders ───────────────────────────────────────────────────────
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{po}/approve',  [PurchaseOrderController::class, 'approve']);
    Route::post('purchase-orders/{po}/send',     [PurchaseOrderController::class, 'send']);
    Route::post('purchase-orders/{po}/cancel',   [PurchaseOrderController::class, 'cancel']);

    Route::apiResource('purchase-orders.receipts', GoodsReceiptController::class)->shallow();
    Route::post('receipts/{grn}/qc-approve', [GoodsReceiptController::class, 'qcApprove']);
    Route::post('receipts/{grn}/post',       [GoodsReceiptController::class, 'post']);

    // ── Sales Orders ──────────────────────────────────────────────────────────
    Route::apiResource('sales-orders', SalesOrderController::class);
    Route::post('sales-orders/{so}/confirm',          [SalesOrderController::class, 'confirm']);
    Route::post('sales-orders/{so}/cancel',           [SalesOrderController::class, 'cancel']);
    Route::post('sales-orders/{so}/ship',             [SalesOrderController::class, 'ship']);
    Route::post('sales-orders/{so}/pick-list',        [SalesOrderController::class, 'generatePickList']);
    Route::post('sales-orders/{so}/allocate',         [SalesOrderController::class, 'allocate']);
    Route::post('sales-orders/{so}/returns',          [SalesOrderController::class, 'createReturn']);

    Route::get('pick-lists',                          [SalesOrderController::class, 'pickLists']);
    Route::post('pick-lists/{list}/lines/{line}/pick',[SalesOrderController::class, 'recordPick']);
    Route::post('pick-lists/{list}/complete',         [SalesOrderController::class, 'completePick']);

    Route::get('returns',                     [SalesOrderController::class, 'returns']);
    Route::post('returns/{rma}/approve',      [SalesOrderController::class, 'approveReturn']);
    Route::post('returns/{rma}/receive',      [SalesOrderController::class, 'receiveReturn']);

    // ── Allocations ───────────────────────────────────────────────────────────
    Route::prefix('allocations')->group(function () {
        Route::post('run',    [AllocationController::class, 'run']);       // trigger algorithm
        Route::get('/',       [AllocationController::class, 'index']);     // active allocations
        Route::delete('{id}', [AllocationController::class, 'release']);  // release reservation
    });

    // ── Physical Counts ───────────────────────────────────────────────────────
    Route::apiResource('physical-counts', PhysicalCountController::class);
    Route::post('physical-counts/{count}/start',            [PhysicalCountController::class, 'start']);
    Route::post('physical-counts/{count}/items/{item}/count', [PhysicalCountController::class, 'recordCount']);
    Route::post('physical-counts/{count}/items/{item}/recount', [PhysicalCountController::class, 'recordRecount']);
    Route::post('physical-counts/{count}/reconcile',        [PhysicalCountController::class, 'reconcile']);
    Route::post('physical-counts/{count}/approve',          [PhysicalCountController::class, 'approve']);

    // ── Reorder Rules ─────────────────────────────────────────────────────────
    Route::apiResource('reorder-rules', ReorderRuleController::class);
    Route::post('reorder-rules/evaluate', [ReorderRuleController::class, 'evaluate']);

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('stock-valuation',   [ReportController::class, 'stockValuation']);
        Route::get('cogs',              [ReportController::class, 'cogs']);
        Route::get('turnover',          [ReportController::class, 'turnover']);
        Route::get('ageing',            [ReportController::class, 'ageing']);
        Route::get('slow-moving',       [ReportController::class, 'slowMoving']);
        Route::get('gross-margin',      [ReportController::class, 'grossMargin']);
        Route::get('abc-analysis',      [ReportController::class, 'abcAnalysis']);
        Route::get('period-comparison', [ReportController::class, 'periodComparison']);
        Route::get('snapshot/{period}', [ReportController::class, 'snapshot']);
        Route::get('expiry-calendar',   [ReportController::class, 'expiryCalendar']);
        Route::get('negative-stock',    [ReportController::class, 'negativeStock']);
        Route::get('cost-variances',    [ReportController::class, 'costVariances']);
    });

    // ── Audit Logs ────────────────────────────────────────────────────────────
    Route::get('audit-logs',                [AuditLogController::class, 'index']);
    Route::get('audit-logs/{type}/{id}',    [AuditLogController::class, 'forModel']);
    Route::get('audit-logs/user/{userId}',  [AuditLogController::class, 'forUser']);
});
