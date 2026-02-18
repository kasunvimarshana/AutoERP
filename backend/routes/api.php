<?php

use App\Http\Controllers\Api\FeatureFlagController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MetadataController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Modules\CRM\Http\Controllers\CustomerController;
use App\Modules\Finance\Http\Controllers\AccountController;
use App\Modules\Finance\Http\Controllers\FinancialReportController;
use App\Modules\Finance\Http\Controllers\FiscalYearController;
use App\Modules\Finance\Http\Controllers\JournalEntryController;
use App\Modules\IAM\Http\Controllers\PermissionController;
use App\Modules\IAM\Http\Controllers\RoleController;
use App\Modules\IAM\Http\Controllers\UserController;
use App\Modules\Inventory\Http\Controllers\ProductController;
use App\Modules\Inventory\Http\Controllers\StockMovementController;
use App\Modules\Manufacturing\Http\Controllers\BillOfMaterialController;
use App\Modules\Manufacturing\Http\Controllers\ProductionOrderController;
use App\Modules\Manufacturing\Http\Controllers\WorkOrderController;
use App\Modules\POS\Http\Controllers\InvoiceController;
use App\Modules\POS\Http\Controllers\PaymentController;
use App\Modules\POS\Http\Controllers\QuotationController;
use App\Modules\POS\Http\Controllers\SalesOrderController;
use App\Modules\Procurement\Http\Controllers\PurchaseOrderController;
use App\Modules\Procurement\Http\Controllers\SupplierController;
use App\Modules\Reporting\Http\Controllers\AnalyticsController;
use App\Modules\Reporting\Http\Controllers\DashboardController;
use App\Modules\Reporting\Http\Controllers\ReportController;
use App\Modules\Reporting\Http\Controllers\ScheduledReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// API v1 Routes
Route::prefix('v1')->group(function () {

    // Public routes
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'AutoERP API is running',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Authentication routes (public)
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {

        // Authentication routes (protected)
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('user', [AuthController::class, 'user']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'profile']);
            Route::put('change-password', [AuthController::class, 'changePassword']);
        });

        // Metadata API Routes
        Route::prefix('metadata')->group(function () {
            // Get metadata catalog and entity info
            Route::get('catalog', [MetadataController::class, 'catalog']);
            Route::get('entity/{name}', [MetadataController::class, 'entity']);
            Route::get('module/{module}', [MetadataController::class, 'module']);
            Route::get('entity/{entityName}/fields', [MetadataController::class, 'fieldConfig']);
            Route::get('entity/{entityName}/validation', [MetadataController::class, 'validationRules']);
            Route::post('cache/clear', [MetadataController::class, 'clearCache']);

            // Admin routes for managing metadata
            Route::post('entities', [MetadataController::class, 'createEntity']);
            Route::put('entities/{id}', [MetadataController::class, 'updateEntity']);
        });

        // Menu API Routes
        Route::prefix('menu')->group(function () {
            Route::get('/', [MenuController::class, 'index']); // User menu
            Route::get('all', [MenuController::class, 'all']); // Admin view
            Route::post('/', [MenuController::class, 'store']);
            Route::put('{id}', [MenuController::class, 'update']);
            Route::delete('{id}', [MenuController::class, 'destroy']);
            Route::post('reorder', [MenuController::class, 'reorder']);
        });

        // Feature Flags API Routes
        Route::prefix('features')->group(function () {
            Route::get('/', [FeatureFlagController::class, 'index']); // Enabled features
            Route::get('check/{name}', [FeatureFlagController::class, 'check']);
            Route::post('check-multiple', [FeatureFlagController::class, 'checkMultiple']);
            Route::get('module/{module}', [FeatureFlagController::class, 'byModule']);
            Route::post('{name}/enable', [FeatureFlagController::class, 'enable']);
            Route::post('{name}/disable', [FeatureFlagController::class, 'disable']);
            Route::post('/', [FeatureFlagController::class, 'store']);
            Route::put('{id}', [FeatureFlagController::class, 'update']);
        });

        // Products (root level)
        Route::get('products/search', [ProductController::class, 'search']);
        Route::get('products/below-reorder-level', [ProductController::class, 'belowReorderLevel']);
        Route::get('products/{id}/stock-history', [ProductController::class, 'stockHistory']);
        Route::apiResource('products', ProductController::class);

        // Inventory Module Routes
        Route::prefix('inventory')->group(function () {

            // Products (also available here for backward compatibility)
            Route::get('products/search', [ProductController::class, 'search']);
            Route::get('products/below-reorder-level', [ProductController::class, 'belowReorderLevel']);
            Route::get('products/{id}/stock-history', [ProductController::class, 'stockHistory']);
            Route::apiResource('products', ProductController::class);

            // Stock Movements
            Route::prefix('stock-movements')->group(function () {
                Route::get('types', [StockMovementController::class, 'types']);
                Route::get('history', [StockMovementController::class, 'history']);
                Route::post('adjustment', [StockMovementController::class, 'adjustment']);
                Route::post('transfer', [StockMovementController::class, 'transfer']);
            });

        });

        // CRM Module Routes
        Route::prefix('crm')->group(function () {
            Route::get('customers/search', [CustomerController::class, 'search']);
            Route::apiResource('customers', CustomerController::class);
        });

        // Procurement Module Routes
        Route::prefix('procurement')->group(function () {

            // Suppliers
            Route::get('suppliers/search', [SupplierController::class, 'search']);
            Route::get('suppliers/active', [SupplierController::class, 'active']);
            Route::apiResource('suppliers', SupplierController::class);

            // Purchase Orders
            Route::get('purchase-orders/search', [PurchaseOrderController::class, 'search']);
            Route::get('purchase-orders/pending', [PurchaseOrderController::class, 'pending']);
            Route::post('purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve']);
            Route::post('purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive']);
            Route::post('purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
            Route::apiResource('purchase-orders', PurchaseOrderController::class);

        });

        // IAM Module Routes
        Route::prefix('iam')->group(function () {

            // Users
            Route::get('users/search', [UserController::class, 'search']);
            Route::get('users/active', [UserController::class, 'active']);
            Route::post('users/{id}/assign-roles', [UserController::class, 'assignRoles']);
            Route::post('users/{id}/sync-roles', [UserController::class, 'syncRoles']);
            Route::get('users/{id}/roles', [UserController::class, 'getRoles']);
            Route::get('users/{id}/permissions', [UserController::class, 'getPermissions']);
            Route::apiResource('users', UserController::class);

            // Roles
            Route::get('roles/system', [RoleController::class, 'systemRoles']);
            Route::get('roles/custom', [RoleController::class, 'customRoles']);
            Route::post('roles/{id}/assign-permissions', [RoleController::class, 'assignPermissions']);
            Route::post('roles/{id}/sync-permissions', [RoleController::class, 'syncPermissions']);
            Route::get('roles/{id}/permissions', [RoleController::class, 'getPermissions']);
            Route::get('roles/{id}/users', [RoleController::class, 'getUsers']);
            Route::apiResource('roles', RoleController::class);

            // Permissions (read-only)
            Route::get('permissions/grouped', [PermissionController::class, 'grouped']);
            Route::get('permissions/{id}/roles', [PermissionController::class, 'getRoles']);
            Route::get('permissions/{id}', [PermissionController::class, 'show']);
            Route::get('permissions', [PermissionController::class, 'index']);

        });

        // POS (Point of Sale) Module Routes
        Route::prefix('pos')->group(function () {

            // Quotations
            Route::get('quotations/expired', [QuotationController::class, 'expired']);
            Route::get('quotations/customer/{customerId}', [QuotationController::class, 'byCustomer']);
            Route::post('quotations/{id}/convert-to-sales-order', [QuotationController::class, 'convertToSalesOrder']);
            Route::apiResource('quotations', QuotationController::class);

            // Sales Orders
            Route::get('sales-orders/search', [SalesOrderController::class, 'search']);
            Route::get('sales-orders/status/{status}', [SalesOrderController::class, 'byStatus']);
            Route::get('sales-orders/customer/{customerId}', [SalesOrderController::class, 'byCustomer']);
            Route::post('sales-orders/{id}/confirm', [SalesOrderController::class, 'confirm']);
            Route::post('sales-orders/{id}/cancel', [SalesOrderController::class, 'cancel']);
            Route::apiResource('sales-orders', SalesOrderController::class);

            // Invoices
            Route::get('invoices/overdue', [InvoiceController::class, 'overdue']);
            Route::get('invoices/status/{status}', [InvoiceController::class, 'byStatus']);
            Route::get('invoices/customer/{customerId}', [InvoiceController::class, 'byCustomer']);
            Route::post('invoices/from-sales-order/{salesOrderId}', [InvoiceController::class, 'createFromSalesOrder']);
            Route::apiResource('invoices', InvoiceController::class);

            // Payments
            Route::get('payments/invoice/{invoiceId}', [PaymentController::class, 'byInvoice']);
            Route::get('payments/customer/{customerId}', [PaymentController::class, 'byCustomer']);
            Route::post('payments/{id}/void', [PaymentController::class, 'void']);
            Route::apiResource('payments', PaymentController::class)->except(['update']);

        });

        // Manufacturing Module Routes
        Route::prefix('manufacturing')->group(function () {

            // Bill of Materials
            Route::get('boms/search', [BillOfMaterialController::class, 'search']);
            Route::get('boms/product/{productId}', [BillOfMaterialController::class, 'byProduct']);
            Route::get('boms/product/{productId}/latest-active', [BillOfMaterialController::class, 'latestActive']);
            Route::post('boms/{id}/create-version', [BillOfMaterialController::class, 'createVersion']);
            Route::apiResource('boms', BillOfMaterialController::class);

            // Production Orders
            Route::get('production-orders/search', [ProductionOrderController::class, 'search']);
            Route::get('production-orders/status', [ProductionOrderController::class, 'byStatus']);
            Route::get('production-orders/in-progress', [ProductionOrderController::class, 'inProgress']);
            Route::get('production-orders/overdue', [ProductionOrderController::class, 'overdue']);
            Route::post('production-orders/{id}/release', [ProductionOrderController::class, 'release']);
            Route::post('production-orders/{id}/start', [ProductionOrderController::class, 'start']);
            Route::post('production-orders/{id}/consume-materials', [ProductionOrderController::class, 'consumeMaterials']);
            Route::post('production-orders/{id}/complete', [ProductionOrderController::class, 'complete']);
            Route::post('production-orders/{id}/cancel', [ProductionOrderController::class, 'cancel']);
            Route::apiResource('production-orders', ProductionOrderController::class);

            // Work Orders
            Route::get('work-orders/search', [WorkOrderController::class, 'search']);
            Route::get('work-orders/status', [WorkOrderController::class, 'byStatus']);
            Route::get('work-orders/pending', [WorkOrderController::class, 'pending']);
            Route::get('work-orders/in-progress', [WorkOrderController::class, 'inProgress']);
            Route::get('work-orders/overdue', [WorkOrderController::class, 'overdue']);
            Route::get('work-orders/my-work-orders', [WorkOrderController::class, 'myWorkOrders']);
            Route::get('work-orders/production-order/{productionOrderId}', [WorkOrderController::class, 'byProductionOrder']);
            Route::post('work-orders/{id}/start', [WorkOrderController::class, 'start']);
            Route::post('work-orders/{id}/complete', [WorkOrderController::class, 'complete']);
            Route::post('work-orders/{id}/cancel', [WorkOrderController::class, 'cancel']);
            Route::apiResource('work-orders', WorkOrderController::class);

        });

        // Finance Module Routes
        Route::prefix('finance')->group(function () {

            // Accounts (Chart of Accounts)
            Route::get('accounts/by-type', [AccountController::class, 'byType']);
            Route::get('accounts/root', [AccountController::class, 'rootAccounts']);
            Route::get('accounts/active', [AccountController::class, 'activeAccounts']);
            Route::get('accounts/search', [AccountController::class, 'search']);
            Route::get('accounts/{id}/balance', [AccountController::class, 'balance']);
            Route::apiResource('accounts', AccountController::class);

            // Journal Entries
            Route::get('journal-entries/by-status', [JournalEntryController::class, 'byStatus']);
            Route::get('journal-entries/draft', [JournalEntryController::class, 'draft']);
            Route::get('journal-entries/posted', [JournalEntryController::class, 'posted']);
            Route::get('journal-entries/search', [JournalEntryController::class, 'search']);
            Route::get('journal-entries/generate-entry-number', [JournalEntryController::class, 'generateEntryNumber']);
            Route::post('journal-entries/{id}/post', [JournalEntryController::class, 'post']);
            Route::post('journal-entries/{id}/void', [JournalEntryController::class, 'void']);
            Route::apiResource('journal-entries', JournalEntryController::class);

            // Financial Reports
            Route::post('reports/trial-balance', [FinancialReportController::class, 'trialBalance']);
            Route::post('reports/profit-and-loss', [FinancialReportController::class, 'profitAndLoss']);
            Route::post('reports/balance-sheet', [FinancialReportController::class, 'balanceSheet']);
            Route::post('reports/account-ledger/{accountId}', [FinancialReportController::class, 'accountLedger']);
            Route::post('reports/general-ledger', [FinancialReportController::class, 'generalLedger']);

            // Fiscal Years
            Route::get('fiscal-years/open', [FiscalYearController::class, 'open']);
            Route::get('fiscal-years/closed', [FiscalYearController::class, 'closed']);
            Route::get('fiscal-years/current', [FiscalYearController::class, 'current']);
            Route::post('fiscal-years/{id}/close', [FiscalYearController::class, 'close']);
            Route::apiResource('fiscal-years', FiscalYearController::class);

        });

        // Notifications Module Routes
        Route::prefix('notifications')->group(function () {

            // Push Notifications
            Route::post('push/subscribe', [NotificationController::class, 'subscribePush']);
            Route::post('push/unsubscribe', [NotificationController::class, 'unsubscribePush']);
            Route::get('push/subscriptions', [NotificationController::class, 'getPushSubscriptions']);
            Route::post('push/test', [NotificationController::class, 'sendTestNotification']);

            // Notification Preferences
            Route::get('preferences', [NotificationController::class, 'getPreferences']);
            Route::put('preferences', [NotificationController::class, 'updatePreferences']);

            // Notification History
            Route::get('history', [NotificationController::class, 'getHistory']);
            Route::post('{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
            Route::post('mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
            Route::delete('{id}', [NotificationController::class, 'deleteNotification']);

        });

        // Reporting & Analytics Module Routes
        Route::prefix('reports')->group(function () {

            // Reports - specialized routes first
            Route::get('by-module', [ReportController::class, 'getByModule']);

            // Dashboards (before generic report resource routes)
            Route::prefix('dashboards')->group(function () {
                Route::get('default', [DashboardController::class, 'getDefault']);
                Route::post('{id}/set-default', [DashboardController::class, 'setAsDefault']);
                Route::post('widgets', [DashboardController::class, 'addWidget']);
                Route::put('widgets/{widgetId}', [DashboardController::class, 'updateWidget']);
                Route::delete('widgets/{widgetId}', [DashboardController::class, 'removeWidget']);
                Route::post('{id}/reorder-widgets', [DashboardController::class, 'reorderWidgets']);
                Route::apiResource('/', DashboardController::class)->parameters(['' => 'id']);
            });

            // Analytics & KPIs
            Route::prefix('analytics')->group(function () {
                Route::get('kpis', [AnalyticsController::class, 'getKPIs']);
                Route::get('revenue', [AnalyticsController::class, 'getTotalRevenue']);
                Route::get('expenses', [AnalyticsController::class, 'getTotalExpenses']);
                Route::get('gross-profit-margin', [AnalyticsController::class, 'getGrossProfitMargin']);
                Route::get('net-profit-margin', [AnalyticsController::class, 'getNetProfitMargin']);
                Route::get('inventory-turnover-ratio', [AnalyticsController::class, 'getInventoryTurnoverRatio']);
                Route::get('days-sales-outstanding', [AnalyticsController::class, 'getDaysSalesOutstanding']);
                Route::get('order-fulfillment-rate', [AnalyticsController::class, 'getOrderFulfillmentRate']);
                Route::get('production-efficiency', [AnalyticsController::class, 'getProductionEfficiency']);
                Route::get('customer-acquisition-cost', [AnalyticsController::class, 'getCustomerAcquisitionCost']);
                Route::get('average-order-value', [AnalyticsController::class, 'getAverageOrderValue']);
            });

            // Scheduled Reports
            Route::prefix('scheduled')->group(function () {
                Route::get('due', [ScheduledReportController::class, 'getDueReports']);
                Route::post('process', [ScheduledReportController::class, 'processDueReports']);
                Route::get('report/{reportId}', [ScheduledReportController::class, 'getForReport']);
                Route::apiResource('/', ScheduledReportController::class)->parameters(['' => 'id']);
            });

            // Reports - generic resource routes last
            Route::post('{id}/execute', [ReportController::class, 'execute']);
            Route::post('{id}/export', [ReportController::class, 'export']);
            Route::apiResource('/', ReportController::class)->parameters(['' => 'id']);

        });

    });
});
