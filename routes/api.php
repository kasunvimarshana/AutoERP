<?php

use App\Http\Controllers\Api\V1\AccountingController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\FileManagerController;
use App\Http\Controllers\Api\V1\HrController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PriceListController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\RbacController;
use App\Http\Controllers\Api\V1\ReportingController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\WebhookController;
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

        // Inventory / Warehouse routes (tenant-scoped)
        Route::apiResource('warehouses', WarehouseController::class);

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
        });

        // Webhook routes
        Route::get('webhooks/{id}/deliveries', [WebhookController::class, 'deliveries'])->name('api.v1.webhooks.deliveries');
        Route::post('webhooks/{id}/test', [WebhookController::class, 'test'])->name('api.v1.webhooks.test');
        Route::apiResource('webhooks', WebhookController::class)->except(['show']);
    });
});
