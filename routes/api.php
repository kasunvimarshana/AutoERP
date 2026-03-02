<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Interfaces\Http\Controllers\AuthController;
use Modules\Core\Interfaces\Http\Controllers\TenantController;
use Modules\Core\Interfaces\Http\Controllers\BusinessLocationController;
use Modules\Product\Interfaces\Http\Controllers\ProductController;
use Modules\Inventory\Interfaces\Http\Controllers\InventoryController;
use Modules\Sales\Interfaces\Http\Controllers\SaleController;
use Modules\Sales\Interfaces\Http\Controllers\POSController;
use Modules\Procurement\Interfaces\Http\Controllers\PurchaseController;
use Modules\CRM\Interfaces\Http\Controllers\LeadController;
use Modules\CRM\Interfaces\Http\Controllers\OpportunityController;
use Modules\Accounting\Interfaces\Http\Controllers\AccountingController;
use Modules\Accounting\Interfaces\Http\Controllers\TaxRateController;
use Modules\Manufacturing\Interfaces\Http\Controllers\ManufacturingController;
use Modules\Sales\Interfaces\Http\Controllers\ContactController;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| All routes are versioned under /api/v1
| Tenant middleware enforces row-level isolation on every authenticated route
*/

Route::prefix('v1')->group(function (): void {

    // ── Auth (no tenant required) ─────────────────────────────────────────
    Route::prefix('auth')->group(function (): void {
        Route::post('login',    [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);

        Route::middleware('auth:api')->group(function (): void {
            Route::post('logout',  [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me',       [AuthController::class, 'me']);
        });
    });

    // ── Health check ──────────────────────────────────────────────────────
    Route::get('health', fn () => response()->json([
        'success' => true,
        'message' => 'OK',
        'data'    => ['timestamp' => now()->toIso8601String()],
        'errors'  => null,
    ]));

    // ── Authenticated + tenant-scoped routes ──────────────────────────────
    Route::middleware(['auth:api', 'tenant'])->group(function (): void {

        // Core — Tenants (super-admin only in production)
        Route::apiResource('tenants', TenantController::class);

        // Core — Business Locations
        Route::apiResource('business-locations', BusinessLocationController::class);

        // Products
        Route::apiResource('products', ProductController::class);
        Route::get('products/{id}/barcode', [ProductController::class, 'barcode']);

        // Inventory
        Route::prefix('inventory')->group(function (): void {
            Route::get('stock/{productId}/{warehouseId}', [InventoryController::class, 'getStockLevel']);
            Route::post('adjust',   [InventoryController::class, 'adjust']);
            Route::post('transfer', [InventoryController::class, 'transfer']);
            Route::get('history',   [InventoryController::class, 'history']);
        });

        // Sales
        Route::apiResource('sales', SaleController::class);

        // POS
        Route::prefix('pos')->group(function (): void {
            Route::post('transaction', [POSController::class, 'createTransaction']);
            Route::get('registers',    [POSController::class, 'registers']);
            Route::post('open-register',  [POSController::class, 'openRegister']);
            Route::post('close-register', [POSController::class, 'closeRegister']);
        });

        // Procurement
        Route::apiResource('purchase-orders', PurchaseController::class);
        Route::prefix('procurement')->group(function (): void {
            Route::post('receive/{id}', [PurchaseController::class, 'receive']);
        });

        // CRM
        Route::apiResource('leads', LeadController::class);
        Route::prefix('crm')->group(function (): void {
            Route::post('leads/{id}/convert', [LeadController::class, 'convert']);
            Route::get('pipeline',            [OpportunityController::class, 'pipeline']);
        });

        // CRM — Opportunities
        Route::apiResource('opportunities', OpportunityController::class);

        // Contacts (customers & suppliers)
        Route::apiResource('contacts', ContactController::class);

        // Accounting
        Route::prefix('accounting')->group(function (): void {
            Route::get('accounts',        [AccountingController::class, 'accounts']);
            Route::post('accounts',       [AccountingController::class, 'createAccount']);
            Route::get('journal',         [AccountingController::class, 'journal']);
            Route::post('journal',        [AccountingController::class, 'postEntry']);
            Route::get('trial-balance',   [AccountingController::class, 'trialBalance']);
            Route::get('profit-and-loss', [AccountingController::class, 'profitAndLoss']);
            Route::get('balance-sheet',   [AccountingController::class, 'balanceSheet']);
            Route::apiResource('tax-rates', TaxRateController::class);
        });

        // Manufacturing
        Route::prefix('manufacturing')->group(function (): void {
            Route::get('boms',                          [ManufacturingController::class, 'listBoms']);
            Route::post('boms',                         [ManufacturingController::class, 'createBom']);
            Route::get('boms/{id}',                     [ManufacturingController::class, 'showBom']);
            Route::get('orders',                        [ManufacturingController::class, 'listOrders']);
            Route::post('orders',                       [ManufacturingController::class, 'createOrder']);
            Route::get('orders/{id}',                   [ManufacturingController::class, 'showOrder']);
            Route::patch('orders/{id}/status',          [ManufacturingController::class, 'updateOrderStatus']);
            Route::patch('orders/{id}/complete',         [ManufacturingController::class, 'completeOrder']);
        });
    });
});
