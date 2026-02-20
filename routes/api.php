<?php

use App\Http\Controllers\Api\V1\AccountingController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BarcodeController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\BusinessLocationController;
use App\Http\Controllers\Api\V1\BusinessSettingController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\CustomerGroupController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\FileManagerController;
use App\Http\Controllers\Api\V1\HrController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\InvoiceLayoutController;
use App\Http\Controllers\Api\V1\InvoiceSchemeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OpeningStockController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PaymentAccountController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\PosReturnController;
use App\Http\Controllers\Api\V1\PriceListController;
use App\Http\Controllers\Api\V1\PrinterController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductRackController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\PurchaseReturnController;
use App\Http\Controllers\Api\V1\RbacController;
use App\Http\Controllers\Api\V1\ReportingController;
use App\Http\Controllers\Api\V1\RestaurantController;
use App\Http\Controllers\Api\V1\SalesCommissionAgentController;
use App\Http\Controllers\Api\V1\SellingPriceGroupController;
use App\Http\Controllers\Api\V1\StockAdjustmentController;
use App\Http\Controllers\Api\V1\StockTransferController;
use App\Http\Controllers\Api\V1\TaxRateController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VariationTemplateController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth routes (public)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth')
            ->name('api.v1.auth.login');
        Route::middleware('auth:api')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('api.v1.auth.refresh');
            Route::get('me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        });
    });

    // Authenticated API routes
    Route::middleware(['auth:api'])->group(function () {
        // Platform admin routes
        Route::prefix('platform')->group(function () {
            Route::apiResource('tenants', TenantController::class)->except(['destroy']);
            Route::patch('tenants/{id}/suspend', [TenantController::class, 'suspend'])->name('api.v1.tenants.suspend');
            Route::patch('tenants/{id}/activate', [TenantController::class, 'activate'])->name('api.v1.tenants.activate');
        });

        // Organization routes (tenant-scoped); tree must be before apiResource
        Route::get('organizations/tree', [OrganizationController::class, 'tree'])->name('api.v1.organizations.tree');
        Route::apiResource('organizations', OrganizationController::class)->except(['show']);

        // User routes (tenant-scoped)
        Route::patch('users/{id}/suspend', [UserController::class, 'suspend'])->name('api.v1.users.suspend');
        Route::patch('users/{id}/activate', [UserController::class, 'activate'])->name('api.v1.users.activate');
        Route::apiResource('users', UserController::class)->except(['show', 'destroy']);

        // Product routes (tenant-scoped)
        Route::apiResource('products', ProductController::class);

        // Product Category routes
        Route::get('product-categories/tree', [ProductCategoryController::class, 'tree'])->name('api.v1.product-categories.tree');
        Route::apiResource('product-categories', ProductCategoryController::class)->except(['show']);

        // Inventory / Warehouse routes (tenant-scoped)
        Route::apiResource('warehouses', WarehouseController::class);

        // Inventory stock & alert routes
        Route::prefix('inventory')->name('api.v1.inventory.')->group(function () {
            Route::get('stock', [InventoryController::class, 'stock'])->name('stock');
            Route::get('alerts/low-stock', [InventoryController::class, 'lowStock'])->name('alerts.low-stock');
            Route::get('alerts/expiring', [InventoryController::class, 'expiring'])->name('alerts.expiring');
            Route::get('fifo-cost', [InventoryController::class, 'fifoCost'])->name('fifo-cost');
        });

        // Order routes
        Route::patch('orders/{id}/confirm', [OrderController::class, 'confirm'])->name('api.v1.orders.confirm');
        Route::patch('orders/{id}/cancel', [OrderController::class, 'cancel'])->name('api.v1.orders.cancel');
        Route::apiResource('orders', OrderController::class)->only(['index', 'store']);

        // Invoice routes
        Route::patch('invoices/{id}/send', [InvoiceController::class, 'send'])->name('api.v1.invoices.send');
        Route::patch('invoices/{id}/void', [InvoiceController::class, 'void'])->name('api.v1.invoices.void');
        Route::apiResource('invoices', InvoiceController::class)->only(['index', 'store']);

        // Payment routes
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store']);

        // RBAC routes
        Route::get('roles/permissions', [RbacController::class, 'permissions'])->name('api.v1.roles.permissions');
        Route::patch('roles/{id}/sync-permissions', [RbacController::class, 'syncPermissions'])->name('api.v1.roles.sync-permissions');
        Route::apiResource('roles', RbacController::class)->except(['show']);

        // Price list routes
        Route::get('price-lists/{id}/rules', [PriceListController::class, 'rules'])->name('api.v1.price-lists.rules.index');
        Route::post('price-lists/{id}/rules', [PriceListController::class, 'storeRule'])->name('api.v1.price-lists.rules.store');
        Route::apiResource('price-lists', PriceListController::class)->except(['show']);

        // Audit routes
        Route::get('audit-logs', [AuditController::class, 'index'])->name('api.v1.audit-logs.index');

        // CRM routes
        Route::get('crm/contacts', [ContactController::class, 'index'])->name('api.v1.crm.contacts.index');
        Route::post('crm/contacts', [ContactController::class, 'store'])->name('api.v1.crm.contacts.store');
        Route::put('crm/contacts/{id}', [ContactController::class, 'update'])->name('api.v1.crm.contacts.update');
        Route::delete('crm/contacts/{id}', [ContactController::class, 'destroy'])->name('api.v1.crm.contacts.destroy');
        Route::get('crm/leads', [ContactController::class, 'leads'])->name('api.v1.crm.leads.index');
        Route::post('crm/leads', [ContactController::class, 'storeLead'])->name('api.v1.crm.leads.store');
        Route::patch('crm/leads/{id}/convert', [ContactController::class, 'convertLead'])->name('api.v1.crm.leads.convert');
        Route::get('crm/opportunities', [ContactController::class, 'opportunities'])->name('api.v1.crm.opportunities.index');
        Route::post('crm/opportunities', [ContactController::class, 'storeOpportunity'])->name('api.v1.crm.opportunities.store');
        // User contact access restrictions (user_customer_access)
        Route::get('crm/users/{userId}/contact-access', [ContactController::class, 'userContactAccess'])->name('api.v1.crm.users.contact-access.index');
        Route::put('crm/users/{userId}/contact-access', [ContactController::class, 'syncUserContactAccess'])->name('api.v1.crm.users.contact-access.sync');
        Route::delete('crm/users/{userId}/contact-access', [ContactController::class, 'clearUserContactAccess'])->name('api.v1.crm.users.contact-access.clear');

        // Accounting routes
        Route::prefix('accounting')->group(function () {
            Route::get('accounts', [AccountingController::class, 'indexAccounts'])->name('api.v1.accounting.accounts.index');
            Route::post('accounts', [AccountingController::class, 'storeAccount'])->name('api.v1.accounting.accounts.store');
            Route::put('accounts/{id}', [AccountingController::class, 'updateAccount'])->name('api.v1.accounting.accounts.update');
            Route::get('periods', [AccountingController::class, 'indexPeriods'])->name('api.v1.accounting.periods.index');
            Route::post('periods', [AccountingController::class, 'storePeriod'])->name('api.v1.accounting.periods.store');
            Route::patch('periods/{id}/close', [AccountingController::class, 'closePeriod'])->name('api.v1.accounting.periods.close');
            Route::get('journal-entries', [AccountingController::class, 'indexJournalEntries'])->name('api.v1.accounting.journal-entries.index');
            Route::post('journal-entries', [AccountingController::class, 'storeJournalEntry'])->name('api.v1.accounting.journal-entries.store');
            Route::patch('journal-entries/{id}/post', [AccountingController::class, 'postJournalEntry'])->name('api.v1.accounting.journal-entries.post');
        });

        // HR routes
        Route::prefix('hr')->group(function () {
            Route::get('employees', [HrController::class, 'indexEmployees'])->name('api.v1.hr.employees.index');
            Route::post('employees', [HrController::class, 'storeEmployee'])->name('api.v1.hr.employees.store');
            Route::put('employees/{id}', [HrController::class, 'updateEmployee'])->name('api.v1.hr.employees.update');
            Route::patch('employees/{id}/terminate', [HrController::class, 'terminateEmployee'])->name('api.v1.hr.employees.terminate');
            Route::get('departments', [HrController::class, 'indexDepartments'])->name('api.v1.hr.departments.index');
            Route::post('departments', [HrController::class, 'storeDepartment'])->name('api.v1.hr.departments.store');
            Route::put('departments/{id}', [HrController::class, 'updateDepartment'])->name('api.v1.hr.departments.update');
            Route::get('leave-requests', [HrController::class, 'indexLeaveRequests'])->name('api.v1.hr.leave-requests.index');
            Route::post('leave-requests', [HrController::class, 'storeLeaveRequest'])->name('api.v1.hr.leave-requests.store');
            Route::patch('leave-requests/{id}/approve', [HrController::class, 'approveLeaveRequest'])->name('api.v1.hr.leave-requests.approve');
            Route::patch('leave-requests/{id}/reject', [HrController::class, 'rejectLeaveRequest'])->name('api.v1.hr.leave-requests.reject');
        });

        // Notification routes
        Route::prefix('notifications')->group(function () {
            Route::get('templates', [NotificationController::class, 'indexTemplates'])->name('api.v1.notifications.templates.index');
            Route::post('templates', [NotificationController::class, 'storeTemplate'])->name('api.v1.notifications.templates.store');
            Route::put('templates/{id}', [NotificationController::class, 'updateTemplate'])->name('api.v1.notifications.templates.update');
            Route::post('send', [NotificationController::class, 'send'])->name('api.v1.notifications.send');
            Route::get('logs', [NotificationController::class, 'indexLogs'])->name('api.v1.notifications.logs.index');
        });

        // File Manager routes
        Route::get('files', [FileManagerController::class, 'index'])->name('api.v1.files.index');
        Route::post('files', [FileManagerController::class, 'upload'])->name('api.v1.files.upload');
        Route::delete('files/{id}', [FileManagerController::class, 'destroy'])->name('api.v1.files.destroy');

        // Reporting routes
        Route::prefix('reports')->group(function () {
            Route::get('sales-summary', [ReportingController::class, 'salesSummary'])->name('api.v1.reports.sales-summary');
            Route::get('inventory-summary', [ReportingController::class, 'inventorySummary'])->name('api.v1.reports.inventory-summary');
            Route::get('receivables-summary', [ReportingController::class, 'receivablesSummary'])->name('api.v1.reports.receivables-summary');
            Route::get('top-products', [ReportingController::class, 'topProducts'])->name('api.v1.reports.top-products');
            Route::get('pos-sales-summary', [ReportingController::class, 'posSalesSummary'])->name('api.v1.reports.pos-sales-summary');
            Route::get('purchase-summary', [ReportingController::class, 'purchaseSummary'])->name('api.v1.reports.purchase-summary');
            Route::get('expense-summary', [ReportingController::class, 'expenseSummary'])->name('api.v1.reports.expense-summary');
            // parity reports
            Route::get('profit-loss', [ReportingController::class, 'profitLoss'])->name('api.v1.reports.profit-loss');
            Route::get('tax-report', [ReportingController::class, 'taxReport'])->name('api.v1.reports.tax-report');
            Route::get('stock-expiry', [ReportingController::class, 'stockExpiry'])->name('api.v1.reports.stock-expiry');
            Route::get('register-report', [ReportingController::class, 'registerReport'])->name('api.v1.reports.register-report');
            Route::get('customer-group', [ReportingController::class, 'customerGroupReport'])->name('api.v1.reports.customer-group');
            Route::get('product-sell', [ReportingController::class, 'productSellReport'])->name('api.v1.reports.product-sell');
            Route::get('product-purchase', [ReportingController::class, 'productPurchaseReport'])->name('api.v1.reports.product-purchase');
            Route::get('sales-representative', [ReportingController::class, 'salesRepresentativeReport'])->name('api.v1.reports.sales-representative');
            Route::get('trending-products', [ReportingController::class, 'trendingProducts'])->name('api.v1.reports.trending-products');
            Route::get('lot-report', [ReportingController::class, 'lotReport'])->name('api.v1.reports.lot-report');
        });

        // Webhook routes
        Route::get('webhooks/{id}/deliveries', [WebhookController::class, 'deliveries'])->name('api.v1.webhooks.deliveries');
        Route::post('webhooks/{id}/test', [WebhookController::class, 'test'])->name('api.v1.webhooks.test');
        Route::apiResource('webhooks', WebhookController::class)->except(['show']);

        // Tax Rate routes
        Route::apiResource('tax-rates', TaxRateController::class)->except(['show']);

        // Brand routes
        Route::apiResource('brands', BrandController::class)->except(['show']);

        // Customer Group routes
        Route::apiResource('customer-groups', CustomerGroupController::class)->except(['show']);

        // Business Location routes
        Route::apiResource('business-locations', BusinessLocationController::class)->except(['show']);

        // Payment Account routes
        Route::apiResource('payment-accounts', PaymentAccountController::class)->except(['show']);

        // POS routes
        Route::prefix('pos')->group(function () {
            Route::get('registers', [PosController::class, 'indexRegisters'])->name('api.v1.pos.registers.index');
            Route::post('registers/open', [PosController::class, 'openRegister'])->name('api.v1.pos.registers.open');
            Route::post('registers/close', [PosController::class, 'closeRegister'])->name('api.v1.pos.registers.close');
            Route::post('registers/cash-in-out', [PosController::class, 'cashInOut'])->name('api.v1.pos.registers.cash-in-out');
            Route::get('transactions', [PosController::class, 'indexTransactions'])->name('api.v1.pos.transactions.index');
            Route::post('transactions', [PosController::class, 'storeTransaction'])->name('api.v1.pos.transactions.store');
            Route::patch('transactions/{id}/void', [PosController::class, 'voidTransaction'])->name('api.v1.pos.transactions.void');
        });

        // Purchase / Procurement routes
        Route::patch('purchases/{id}/receive', [PurchaseController::class, 'receive'])->name('api.v1.purchases.receive');
        Route::patch('purchases/{id}/cancel', [PurchaseController::class, 'cancel'])->name('api.v1.purchases.cancel');
        Route::apiResource('purchases', PurchaseController::class)->only(['index', 'store']);

        // Expense routes
        Route::prefix('expenses')->group(function () {
            Route::get('categories', [ExpenseController::class, 'indexCategories'])->name('api.v1.expenses.categories.index');
            Route::post('categories', [ExpenseController::class, 'storeCategory'])->name('api.v1.expenses.categories.store');
            Route::put('categories/{id}', [ExpenseController::class, 'updateCategory'])->name('api.v1.expenses.categories.update');
            Route::get('/', [ExpenseController::class, 'indexExpenses'])->name('api.v1.expenses.index');
            Route::post('/', [ExpenseController::class, 'storeExpense'])->name('api.v1.expenses.store');
            Route::put('{id}', [ExpenseController::class, 'updateExpense'])->name('api.v1.expenses.update');
            Route::delete('{id}', [ExpenseController::class, 'destroyExpense'])->name('api.v1.expenses.destroy');
        });

        // Stock Adjustment routes
        Route::apiResource('stock-adjustments', StockAdjustmentController::class)->only(['index', 'store']);

        // Variation Template routes
        Route::apiResource('variation-templates', VariationTemplateController::class)->except(['show']);

        // Currency routes
        Route::post('currencies/convert', [CurrencyController::class, 'convert'])->name('api.v1.currencies.convert');
        Route::apiResource('currencies', CurrencyController::class)->except(['show']);

        // Selling Price Group routes
        Route::post('selling-price-groups/{id}/prices', [SellingPriceGroupController::class, 'upsertPrice'])->name('api.v1.selling-price-groups.prices.upsert');
        Route::apiResource('selling-price-groups', SellingPriceGroupController::class)->except(['show']);

        // POS Return routes
        Route::apiResource('pos-returns', PosReturnController::class)->only(['index', 'store']);

        // Business Settings routes
        Route::get('settings', [BusinessSettingController::class, 'index'])->name('api.v1.settings.index');
        Route::put('settings', [BusinessSettingController::class, 'upsert'])->name('api.v1.settings.upsert');
        Route::delete('settings/{key}', [BusinessSettingController::class, 'destroy'])->name('api.v1.settings.destroy');

        // Invoice Scheme routes
        Route::patch('invoice-schemes/{id}/set-default', [InvoiceSchemeController::class, 'setDefault'])->name('api.v1.invoice-schemes.set-default');
        Route::get('invoice-schemes/{id}/next-number', [InvoiceSchemeController::class, 'nextNumber'])->name('api.v1.invoice-schemes.next-number');
        Route::apiResource('invoice-schemes', InvoiceSchemeController::class)->except(['show']);

        // Stock Transfer routes
        Route::patch('stock-transfers/{id}/dispatch', [StockTransferController::class, 'dispatch'])->name('api.v1.stock-transfers.dispatch');
        Route::patch('stock-transfers/{id}/receive', [StockTransferController::class, 'receive'])->name('api.v1.stock-transfers.receive');
        Route::patch('stock-transfers/{id}/cancel', [StockTransferController::class, 'cancel'])->name('api.v1.stock-transfers.cancel');
        Route::apiResource('stock-transfers', StockTransferController::class)->only(['index', 'store']);

        // Barcode routes
        Route::patch('barcodes/{id}/set-default', [BarcodeController::class, 'setDefault'])->name('api.v1.barcodes.set-default');
        Route::apiResource('barcodes', BarcodeController::class)->except(['show']);

        // Invoice Layout routes
        Route::patch('invoice-layouts/{id}/set-default', [InvoiceLayoutController::class, 'setDefault'])->name('api.v1.invoice-layouts.set-default');
        Route::apiResource('invoice-layouts', InvoiceLayoutController::class)->except(['show']);

        // Printer routes
        Route::get('printers/capability-profiles', [PrinterController::class, 'capabilityProfiles'])->name('api.v1.printers.capability-profiles');
        Route::get('printers/connection-types', [PrinterController::class, 'connectionTypes'])->name('api.v1.printers.connection-types');
        Route::apiResource('printers', PrinterController::class)->except(['show']);

        // Product Rack routes
        Route::post('product-racks', [ProductRackController::class, 'upsert'])->name('api.v1.product-racks.upsert');
        Route::get('product-racks', [ProductRackController::class, 'index'])->name('api.v1.product-racks.index');
        Route::delete('product-racks/{id}', [ProductRackController::class, 'destroy'])->name('api.v1.product-racks.destroy');

        // Restaurant module routes
        Route::prefix('restaurant')->group(function () {
            Route::get('tables', [RestaurantController::class, 'indexTables'])->name('api.v1.restaurant.tables.index');
            Route::post('tables', [RestaurantController::class, 'storeTable'])->name('api.v1.restaurant.tables.store');
            Route::put('tables/{id}', [RestaurantController::class, 'updateTable'])->name('api.v1.restaurant.tables.update');
            Route::delete('tables/{id}', [RestaurantController::class, 'destroyTable'])->name('api.v1.restaurant.tables.destroy');

            Route::get('modifier-sets', [RestaurantController::class, 'indexModifierSets'])->name('api.v1.restaurant.modifier-sets.index');
            Route::post('modifier-sets', [RestaurantController::class, 'storeModifierSet'])->name('api.v1.restaurant.modifier-sets.store');
            Route::put('modifier-sets/{id}', [RestaurantController::class, 'updateModifierSet'])->name('api.v1.restaurant.modifier-sets.update');
            Route::delete('modifier-sets/{id}', [RestaurantController::class, 'destroyModifierSet'])->name('api.v1.restaurant.modifier-sets.destroy');

            Route::get('bookings', [RestaurantController::class, 'indexBookings'])->name('api.v1.restaurant.bookings.index');
            Route::post('bookings', [RestaurantController::class, 'storeBooking'])->name('api.v1.restaurant.bookings.store');
            Route::put('bookings/{id}', [RestaurantController::class, 'updateBooking'])->name('api.v1.restaurant.bookings.update');
            Route::delete('bookings/{id}', [RestaurantController::class, 'destroyBooking'])->name('api.v1.restaurant.bookings.destroy');

            // Kitchen display (KitchenController)
            Route::get('kitchen', [RestaurantController::class, 'indexKitchenOrders'])->name('api.v1.restaurant.kitchen.index');
            Route::patch('kitchen/{id}/mark-cooked', [RestaurantController::class, 'markAsCooked'])->name('api.v1.restaurant.kitchen.mark-cooked');
            Route::patch('kitchen/{id}/mark-served', [RestaurantController::class, 'markAsServed'])->name('api.v1.restaurant.kitchen.mark-served');
        });

        // Purchase Return routes
        Route::patch('purchase-returns/{id}/cancel', [PurchaseReturnController::class, 'cancel'])->name('api.v1.purchase-returns.cancel');
        Route::apiResource('purchase-returns', PurchaseReturnController::class)->only(['index', 'store']);

        // Opening Stock routes
        Route::get('opening-stock', [OpeningStockController::class, 'index'])->name('api.v1.opening-stock.index');
        Route::post('opening-stock', [OpeningStockController::class, 'store'])->name('api.v1.opening-stock.store');

        // Sales Commission Agent routes
        Route::prefix('sales-commission-agents')->group(function () {
            Route::get('/', [SalesCommissionAgentController::class, 'index'])->name('api.v1.sales-commission-agents.index');
            Route::get('{id}/total-sell', [SalesCommissionAgentController::class, 'totalSell'])->name('api.v1.sales-commission-agents.total-sell');
            Route::get('{id}/total-commission', [SalesCommissionAgentController::class, 'totalCommission'])->name('api.v1.sales-commission-agents.total-commission');
        });

        // Workflow Engine routes
        Route::prefix('workflow')->group(function () {
            Route::get('definitions', [WorkflowController::class, 'index'])->name('api.v1.workflow.definitions.index');
            Route::post('definitions', [WorkflowController::class, 'store'])->name('api.v1.workflow.definitions.store');
            Route::patch('definitions/{id}', [WorkflowController::class, 'update'])->name('api.v1.workflow.definitions.update');
            Route::post('instances', [WorkflowController::class, 'startInstance'])->name('api.v1.workflow.instances.start');
            Route::post('instances/{instanceId}/transition', [WorkflowController::class, 'applyTransition'])->name('api.v1.workflow.instances.transition');
            Route::patch('instances/{instanceId}/cancel', [WorkflowController::class, 'cancelInstance'])->name('api.v1.workflow.instances.cancel');
            Route::get('instances/entity', [WorkflowController::class, 'getEntityInstance'])->name('api.v1.workflow.instances.entity');
        });
    });
});
