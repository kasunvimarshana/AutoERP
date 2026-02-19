<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth routes (public)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('api.v1.auth.login');
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
    });
});
