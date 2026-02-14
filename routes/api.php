<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API Version 1
Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [\App\Modules\Auth\Controllers\AuthController::class, 'login']);
        Route::post('/register', [\App\Modules\Auth\Controllers\AuthController::class, 'register']);
        Route::post('/forgot-password', [\App\Modules\Auth\Controllers\AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [\App\Modules\Auth\Controllers\AuthController::class, 'resetPassword']);
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Authentication routes
        Route::prefix('auth')->group(function () {
            Route::get('/me', [\App\Modules\Auth\Controllers\AuthController::class, 'me']);
            Route::post('/logout', [\App\Modules\Auth\Controllers\AuthController::class, 'logout']);
            Route::post('/refresh', [\App\Modules\Auth\Controllers\AuthController::class, 'refresh']);
        });

        // Tenant routes
        Route::prefix('tenants')->group(function () {
            Route::get('/', [\App\Modules\Tenancy\Controllers\TenantController::class, 'index']);
            Route::post('/', [\App\Modules\Tenancy\Controllers\TenantController::class, 'store']);
            Route::get('/{id}', [\App\Modules\Tenancy\Controllers\TenantController::class, 'show']);
            Route::put('/{id}', [\App\Modules\Tenancy\Controllers\TenantController::class, 'update']);
            Route::delete('/{id}', [\App\Modules\Tenancy\Controllers\TenantController::class, 'destroy']);
        });

        // User routes
        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Modules\User\Controllers\UserController::class, 'index']);
            Route::post('/', [\App\Modules\User\Controllers\UserController::class, 'store']);
            Route::get('/{id}', [\App\Modules\User\Controllers\UserController::class, 'show']);
            Route::put('/{id}', [\App\Modules\User\Controllers\UserController::class, 'update']);
            Route::delete('/{id}', [\App\Modules\User\Controllers\UserController::class, 'destroy']);
            Route::post('/{id}/assign-role', [\App\Modules\User\Controllers\UserController::class, 'assignRole']);
            Route::post('/{id}/sync-permissions', [\App\Modules\User\Controllers\UserController::class, 'syncPermissions']);
        });

        // Branch routes
        Route::prefix('branches')->group(function () {
            Route::get('/', [\App\Modules\Branch\Controllers\BranchController::class, 'index']);
            Route::post('/', [\App\Modules\Branch\Controllers\BranchController::class, 'store']);
            Route::get('/{id}', [\App\Modules\Branch\Controllers\BranchController::class, 'show']);
            Route::put('/{id}', [\App\Modules\Branch\Controllers\BranchController::class, 'update']);
            Route::delete('/{id}', [\App\Modules\Branch\Controllers\BranchController::class, 'destroy']);
            Route::get('/hierarchy/tree', [\App\Modules\Branch\Controllers\BranchController::class, 'hierarchy']);
        });

        // Vendor routes
        Route::prefix('vendors')->group(function () {
            Route::get('/', [\App\Modules\Vendor\Controllers\VendorController::class, 'index']);
            Route::post('/', [\App\Modules\Vendor\Controllers\VendorController::class, 'store']);
            Route::get('/{id}', [\App\Modules\Vendor\Controllers\VendorController::class, 'show']);
            Route::put('/{id}', [\App\Modules\Vendor\Controllers\VendorController::class, 'update']);
            Route::delete('/{id}', [\App\Modules\Vendor\Controllers\VendorController::class, 'destroy']);
            Route::post('/{id}/activate', [\App\Modules\Vendor\Controllers\VendorController::class, 'activate']);
            Route::post('/{id}/deactivate', [\App\Modules\Vendor\Controllers\VendorController::class, 'deactivate']);
        });

        // Customer routes
        Route::prefix('customers')->group(function () {
            Route::get('/', [\App\Modules\Customer\Controllers\CustomerController::class, 'index']);
            Route::post('/', [\App\Modules\Customer\Controllers\CustomerController::class, 'store']);
            Route::get('/{id}', [\App\Modules\Customer\Controllers\CustomerController::class, 'show']);
            Route::put('/{id}', [\App\Modules\Customer\Controllers\CustomerController::class, 'update']);
            Route::delete('/{id}', [\App\Modules\Customer\Controllers\CustomerController::class, 'destroy']);
        });

        // CRM routes
        Route::prefix('crm')->group(function () {
            // Leads
            Route::get('/leads', [\App\Modules\CRM\Controllers\CRMController::class, 'leads']);
            Route::post('/leads', [\App\Modules\CRM\Controllers\CRMController::class, 'createLead']);
            Route::get('/leads/{id}', [\App\Modules\CRM\Controllers\CRMController::class, 'showLead']);
            Route::put('/leads/{id}', [\App\Modules\CRM\Controllers\CRMController::class, 'updateLead']);
            Route::delete('/leads/{id}', [\App\Modules\CRM\Controllers\CRMController::class, 'deleteLead']);
            Route::post('/leads/{id}/convert', [\App\Modules\CRM\Controllers\CRMController::class, 'convertLead']);
            Route::post('/leads/{id}/assign', [\App\Modules\CRM\Controllers\CRMController::class, 'assignLead']);

            // Opportunities
            Route::get('/opportunities', [\App\Modules\CRM\Controllers\CRMController::class, 'opportunities']);
            Route::post('/opportunities', [\App\Modules\CRM\Controllers\CRMController::class, 'createOpportunity']);
            Route::get('/opportunities/{id}', [\App\Modules\CRM\Controllers\CRMController::class, 'showOpportunity']);
            Route::put('/opportunities/{id}', [\App\Modules\CRM\Controllers\CRMController::class, 'updateOpportunity']);
            Route::delete('/opportunities/{id}', [\App\Modules\CRM\Controllers\CRMController::class, 'deleteOpportunity']);
            Route::post('/opportunities/{id}/stage', [\App\Modules\CRM\Controllers\CRMController::class, 'updateOpportunityStage']);

            // Campaigns
            Route::get('/campaigns', [\App\Modules\CRM\Controllers\CRMController::class, 'campaigns']);
            Route::post('/campaigns', [\App\Modules\CRM\Controllers\CRMController::class, 'createCampaign']);
            Route::get('/campaigns/{id}', [\App\Modules\CRM\Controllers\CRMController::class, 'showCampaign']);
            Route::put('/campaigns/{id}', [\App\Modules\CRM\Controllers\CRMController::class, 'updateCampaign']);
            Route::delete('/campaigns/{id}', [\App\Modules\CRM\Controllers\CRMController::class, 'deleteCampaign']);
        });

        // Inventory routes
        Route::prefix('inventory')->group(function () {
            // Products
            Route::get('/products', [\App\Modules\Inventory\Controllers\InventoryController::class, 'products']);
            Route::post('/products', [\App\Modules\Inventory\Controllers\InventoryController::class, 'createProduct']);
            Route::get('/products/{id}', [\App\Modules\Inventory\Controllers\InventoryController::class, 'showProduct']);
            Route::put('/products/{id}', [\App\Modules\Inventory\Controllers\InventoryController::class, 'updateProduct']);
            Route::delete('/products/{id}', [\App\Modules\Inventory\Controllers\InventoryController::class, 'deleteProduct']);

            // Stock
            Route::get('/stock', [\App\Modules\Inventory\Controllers\InventoryController::class, 'stock']);
            Route::post('/stock/adjust', [\App\Modules\Inventory\Controllers\InventoryController::class, 'adjustStock']);
            Route::post('/stock/transfer', [\App\Modules\Inventory\Controllers\InventoryController::class, 'transferStock']);

            // Movements
            Route::get('/movements', [\App\Modules\Inventory\Controllers\InventoryController::class, 'movements']);
        });

        // POS routes
        Route::prefix('pos')->group(function () {
            Route::get('/transactions', [\App\Modules\POS\Controllers\POSController::class, 'transactions']);
            Route::get('/transactions/{id}', [\App\Modules\POS\Controllers\POSController::class, 'showTransaction']);
            Route::post('/checkout', [\App\Modules\POS\Controllers\POSController::class, 'checkout']);
            Route::post('/transactions/{id}/void', [\App\Modules\POS\Controllers\POSController::class, 'voidTransaction']);
            Route::get('/daily-sales', [\App\Modules\POS\Controllers\POSController::class, 'dailySales']);
        });

        // Billing routes
        Route::prefix('billing')->group(function () {
            // Invoices
            Route::get('/invoices', [\App\Modules\Billing\Controllers\BillingController::class, 'invoices']);
            Route::post('/invoices', [\App\Modules\Billing\Controllers\BillingController::class, 'createInvoice']);
            Route::get('/invoices/{id}', [\App\Modules\Billing\Controllers\BillingController::class, 'showInvoice']);
            Route::put('/invoices/{id}', [\App\Modules\Billing\Controllers\BillingController::class, 'updateInvoice']);
            Route::delete('/invoices/{id}', [\App\Modules\Billing\Controllers\BillingController::class, 'deleteInvoice']);
            Route::post('/invoices/{id}/mark-paid', [\App\Modules\Billing\Controllers\BillingController::class, 'markAsPaid']);
            Route::post('/invoices/{id}/send', [\App\Modules\Billing\Controllers\BillingController::class, 'sendToCustomer']);

            // Payments
            Route::get('/payments', [\App\Modules\Billing\Controllers\BillingController::class, 'payments']);
            Route::post('/payments', [\App\Modules\Billing\Controllers\BillingController::class, 'recordPayment']);
        });

        // Fleet routes
        Route::prefix('fleet')->group(function () {
            // Vehicles
            Route::get('/vehicles', [\App\Modules\Fleet\Controllers\FleetController::class, 'vehicles']);
            Route::post('/vehicles', [\App\Modules\Fleet\Controllers\FleetController::class, 'createVehicle']);
            Route::get('/vehicles/{id}', [\App\Modules\Fleet\Controllers\FleetController::class, 'showVehicle']);
            Route::put('/vehicles/{id}', [\App\Modules\Fleet\Controllers\FleetController::class, 'updateVehicle']);
            Route::delete('/vehicles/{id}', [\App\Modules\Fleet\Controllers\FleetController::class, 'deleteVehicle']);
            Route::post('/vehicles/{id}/mileage', [\App\Modules\Fleet\Controllers\FleetController::class, 'updateMileage']);

            // Maintenance
            Route::get('/maintenance', [\App\Modules\Fleet\Controllers\FleetController::class, 'maintenance']);
            Route::post('/maintenance', [\App\Modules\Fleet\Controllers\FleetController::class, 'createMaintenance']);
            Route::get('/maintenance/{id}', [\App\Modules\Fleet\Controllers\FleetController::class, 'showMaintenance']);
            Route::post('/maintenance/{id}/schedule-next', [\App\Modules\Fleet\Controllers\FleetController::class, 'scheduleNextService']);
        });

        // Analytics routes
        Route::prefix('analytics')->group(function () {
            Route::get('/dashboard', [\App\Modules\Analytics\Controllers\AnalyticsController::class, 'dashboard']);
            Route::get('/reports/revenue', [\App\Modules\Analytics\Controllers\AnalyticsController::class, 'revenueReport']);
            Route::get('/reports/inventory', [\App\Modules\Analytics\Controllers\AnalyticsController::class, 'inventoryReport']);
            Route::get('/reports/sales', [\App\Modules\Analytics\Controllers\AnalyticsController::class, 'salesReport']);
            Route::get('/reports/customer', [\App\Modules\Analytics\Controllers\AnalyticsController::class, 'customerReport']);
            Route::get('/reports/fleet', [\App\Modules\Analytics\Controllers\AnalyticsController::class, 'fleetReport']);
        });
    });
});
