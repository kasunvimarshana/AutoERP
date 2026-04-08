<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| KV Inventory Management System — API Routes v1
|--------------------------------------------------------------------------
|
| All routes are:
|  - Versioned under /api/v1
|  - Authenticated via Laravel Passport (OAuth2) — confirmed KVAutoERP stack
|  - Tenant-scoped via middleware
|  - Documented via L5-Swagger annotations on each controller
|
*/

Route::prefix('v1')->middleware(['auth:api', 'tenant.scope'])->group(function () {

    // ── Settings ──────────────────────────────────────────────────────────────
    // User-selectable methods — valuation, rotation, allocation
    Route::prefix('settings/inventory')->group(function () {
        Route::get('/',    'Modules\Settings\Infrastructure\Http\Controllers\InventorySettingsController@show');
        Route::put('/',    'Modules\Settings\Infrastructure\Http\Controllers\InventorySettingsController@update');
        Route::get('/methods', 'Modules\Settings\Infrastructure\Http\Controllers\InventorySettingsController@availableMethods');
    });

    // ── Products ──────────────────────────────────────────────────────────────
    Route::apiResource('products', ProductController::class);
    Route::get('products/{product}/stock',      [ProductController::class, 'stock']);
    Route::get('products/{product}/movements',  [ProductController::class, 'movements']);
    Route::get('products/{product}/valuations', [ProductController::class, 'valuations']);
    Route::post('products/{product}/status',    [ProductController::class, 'changeStatus']);

    // ── Product Variants ──────────────────────────────────────────────────────
    Route::prefix('products/{product}/variants')->group(function () {
        Route::get('/',         'Modules\Product\Infrastructure\Http\Controllers\ProductVariantController@index');
        Route::post('/',        'Modules\Product\Infrastructure\Http\Controllers\ProductVariantController@store');
        Route::put('{variant}', 'Modules\Product\Infrastructure\Http\Controllers\ProductVariantController@update');
        Route::delete('{variant}', 'Modules\Product\Infrastructure\Http\Controllers\ProductVariantController@destroy');
    });

    // ── Warehouses ────────────────────────────────────────────────────────────
    Route::apiResource('warehouses', 'Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController');
    Route::get('warehouses/{warehouse}/zones',     'Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController@zones');
    Route::get('warehouses/{warehouse}/locations', 'Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController@locations');
    Route::get('warehouses/{warehouse}/stock',     'Modules\Warehouse\Infrastructure\Http\Controllers\WarehouseController@stock');

    // ── Batches ───────────────────────────────────────────────────────────────
    Route::apiResource('batches', 'Modules\Batch\Infrastructure\Http\Controllers\BatchController');
    Route::post('batches/{batch}/qc-approve', 'Modules\Batch\Infrastructure\Http\Controllers\BatchController@qcApprove');
    Route::post('batches/{batch}/qc-reject',  'Modules\Batch\Infrastructure\Http\Controllers\BatchController@qcReject');
    Route::post('batches/{batch}/hold',       'Modules\Batch\Infrastructure\Http\Controllers\BatchController@hold');
    Route::post('batches/{batch}/release',    'Modules\Batch\Infrastructure\Http\Controllers\BatchController@release');
    Route::post('batches/{batch}/recall',     'Modules\Batch\Infrastructure\Http\Controllers\BatchController@recall');
    Route::get('batches/{batch}/trace',       'Modules\Batch\Infrastructure\Http\Controllers\BatchController@trace');

    // ── Lots ──────────────────────────────────────────────────────────────────
    Route::apiResource('lots', 'Modules\Batch\Infrastructure\Http\Controllers\LotController');
    Route::get('lots/{lot}/movements', 'Modules\Batch\Infrastructure\Http\Controllers\LotController@movements');

    // ── Serial Numbers ────────────────────────────────────────────────────────
    Route::apiResource('serial-numbers', 'Modules\Batch\Infrastructure\Http\Controllers\SerialNumberController');
    Route::get('serial-numbers/{serial}/history', 'Modules\Batch\Infrastructure\Http\Controllers\SerialNumberController@history');

    // ── Inventory Operations ──────────────────────────────────────────────────
    Route::prefix('inventory')->group(function () {
        Route::get('positions',          'Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@positions');
        Route::get('positions/{product}','Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@productStock');
        Route::get('ledger',             'Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@ledger');
        Route::post('receive',           'Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@receive');
        Route::post('issue',             'Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@issue');
        Route::post('transfer',          'Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@transfer');
        Route::post('adjust',            'Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@adjust');
        Route::get('alerts',             'Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@alerts');
        Route::post('alerts/{alert}/acknowledge', 'Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@acknowledgeAlert');
        Route::get('costing-layers',     'Modules\Inventory\Infrastructure\Http\Controllers\InventoryController@costingLayers');
    });

    // ── Stock Transfers ───────────────────────────────────────────────────────
    Route::apiResource('transfers', 'Modules\Inventory\Infrastructure\Http\Controllers\StockTransferController');
    Route::post('transfers/{transfer}/approve',  'Modules\Inventory\Infrastructure\Http\Controllers\StockTransferController@approve');
    Route::post('transfers/{transfer}/ship',     'Modules\Inventory\Infrastructure\Http\Controllers\StockTransferController@ship');
    Route::post('transfers/{transfer}/receive',  'Modules\Inventory\Infrastructure\Http\Controllers\StockTransferController@receive');
    Route::post('transfers/{transfer}/cancel',   'Modules\Inventory\Infrastructure\Http\Controllers\StockTransferController@cancel');

    // ── Stock Adjustments ─────────────────────────────────────────────────────
    Route::apiResource('adjustments', 'Modules\Inventory\Infrastructure\Http\Controllers\StockAdjustmentController');
    Route::post('adjustments/{adjustment}/approve', 'Modules\Inventory\Infrastructure\Http\Controllers\StockAdjustmentController@approve');
    Route::post('adjustments/{adjustment}/post',    'Modules\Inventory\Infrastructure\Http\Controllers\StockAdjustmentController@post');

    // ── Physical Counts ───────────────────────────────────────────────────────
    Route::apiResource('physical-counts', 'Modules\Inventory\Infrastructure\Http\Controllers\PhysicalCountController');
    Route::post('physical-counts/{count}/start',              'Modules\Inventory\Infrastructure\Http\Controllers\PhysicalCountController@start');
    Route::post('physical-counts/{count}/items/{item}/count', 'Modules\Inventory\Infrastructure\Http\Controllers\PhysicalCountController@recordCount');
    Route::post('physical-counts/{count}/items/{item}/recount','Modules\Inventory\Infrastructure\Http\Controllers\PhysicalCountController@recordRecount');
    Route::post('physical-counts/{count}/reconcile',          'Modules\Inventory\Infrastructure\Http\Controllers\PhysicalCountController@reconcile');
    Route::post('physical-counts/{count}/approve',            'Modules\Inventory\Infrastructure\Http\Controllers\PhysicalCountController@approve');

    // ── Reorder Rules ─────────────────────────────────────────────────────────
    Route::apiResource('reorder-rules', 'Modules\Inventory\Infrastructure\Http\Controllers\ReorderRuleController');
    Route::post('reorder-rules/evaluate', 'Modules\Inventory\Infrastructure\Http\Controllers\ReorderRuleController@evaluate');

    // ── Procurement ───────────────────────────────────────────────────────────
    Route::apiResource('suppliers', 'Modules\Procurement\Infrastructure\Http\Controllers\SupplierController');
    Route::apiResource('purchase-orders', 'Modules\Procurement\Infrastructure\Http\Controllers\PurchaseOrderController');
    Route::post('purchase-orders/{po}/approve',  'Modules\Procurement\Infrastructure\Http\Controllers\PurchaseOrderController@approve');
    Route::post('purchase-orders/{po}/send',     'Modules\Procurement\Infrastructure\Http\Controllers\PurchaseOrderController@send');
    Route::post('purchase-orders/{po}/cancel',   'Modules\Procurement\Infrastructure\Http\Controllers\PurchaseOrderController@cancel');

    Route::apiResource('purchase-orders.receipts', 'Modules\Procurement\Infrastructure\Http\Controllers\GoodsReceiptController')->shallow();
    Route::post('receipts/{grn}/qc-approve', 'Modules\Procurement\Infrastructure\Http\Controllers\GoodsReceiptController@qcApprove');
    Route::post('receipts/{grn}/post',       'Modules\Procurement\Infrastructure\Http\Controllers\GoodsReceiptController@post');

    // ── Sales ─────────────────────────────────────────────────────────────────
    Route::apiResource('customers', 'Modules\Sales\Infrastructure\Http\Controllers\CustomerController');
    Route::apiResource('sales-orders', 'Modules\Sales\Infrastructure\Http\Controllers\SalesOrderController');
    Route::post('sales-orders/{so}/confirm',     'Modules\Sales\Infrastructure\Http\Controllers\SalesOrderController@confirm');
    Route::post('sales-orders/{so}/cancel',      'Modules\Sales\Infrastructure\Http\Controllers\SalesOrderController@cancel');
    Route::post('sales-orders/{so}/allocate',    'Modules\Sales\Infrastructure\Http\Controllers\SalesOrderController@allocate');
    Route::post('sales-orders/{so}/pick-list',   'Modules\Sales\Infrastructure\Http\Controllers\SalesOrderController@generatePickList');
    Route::post('sales-orders/{so}/ship',        'Modules\Sales\Infrastructure\Http\Controllers\SalesOrderController@ship');
    Route::post('sales-orders/{so}/returns',     'Modules\Sales\Infrastructure\Http\Controllers\SalesOrderController@createReturn');

    Route::get('pick-lists',                            'Modules\Sales\Infrastructure\Http\Controllers\PickListController@index');
    Route::post('pick-lists/{list}/lines/{line}/pick',  'Modules\Sales\Infrastructure\Http\Controllers\PickListController@recordPick');
    Route::post('pick-lists/{list}/complete',           'Modules\Sales\Infrastructure\Http\Controllers\PickListController@complete');

    Route::get('returns',                  'Modules\Sales\Infrastructure\Http\Controllers\ReturnController@index');
    Route::post('returns/{rma}/approve',   'Modules\Sales\Infrastructure\Http\Controllers\ReturnController@approve');
    Route::post('returns/{rma}/receive',   'Modules\Sales\Infrastructure\Http\Controllers\ReturnController@receive');

    // ── Production ────────────────────────────────────────────────────────────
    Route::apiResource('boms', 'Modules\Production\Infrastructure\Http\Controllers\BomController');
    Route::apiResource('production-orders', 'Modules\Production\Infrastructure\Http\Controllers\ProductionOrderController');
    Route::post('production-orders/{order}/release',   'Modules\Production\Infrastructure\Http\Controllers\ProductionOrderController@release');
    Route::post('production-orders/{order}/issue',     'Modules\Production\Infrastructure\Http\Controllers\ProductionOrderController@issueComponents');
    Route::post('production-orders/{order}/complete',  'Modules\Production\Infrastructure\Http\Controllers\ProductionOrderController@complete');

    // ── Reporting ─────────────────────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('stock-valuation',   'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@stockValuation');
        Route::get('cogs',              'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@cogs');
        Route::get('turnover',          'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@turnover');
        Route::get('ageing',            'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@ageing');
        Route::get('gross-margin',      'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@grossMargin');
        Route::get('abc-analysis',      'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@abcAnalysis');
        Route::get('expiry-calendar',   'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@expiryCalendar');
        Route::get('negative-stock',    'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@negativeStock');
        Route::get('cost-variances',    'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@costVariances');
        Route::get('period-comparison', 'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@periodComparison');
        Route::get('snapshot/{period}', 'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@snapshot');
        Route::get('batch/{number}',    'Modules\Reporting\Infrastructure\Http\Controllers\ReportController@batchTrace');
    });

    // ── Audit Logs ────────────────────────────────────────────────────────────
    Route::get('audit-logs',             'Modules\Audit\Infrastructure\Http\Controllers\AuditLogController@index');
    Route::get('audit-logs/{type}/{id}', 'Modules\Audit\Infrastructure\Http\Controllers\AuditLogController@forModel');
    Route::get('audit-logs/user/{id}',   'Modules\Audit\Infrastructure\Http\Controllers\AuditLogController@forUser');
    Route::get('audit-logs/module/{m}',  'Modules\Audit\Infrastructure\Http\Controllers\AuditLogController@forModule');
});
