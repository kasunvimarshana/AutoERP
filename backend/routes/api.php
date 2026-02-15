<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\Inventory\InventoryController;
use App\Http\Controllers\Api\CRM\CustomerController;
use App\Http\Controllers\Api\CRM\ContactController;
use App\Http\Controllers\Api\CRM\LeadController;
use App\Http\Controllers\Api\Procurement\VendorController;
use App\Http\Controllers\Api\Procurement\PurchaseOrderController;
use App\Http\Controllers\Api\Procurement\PurchaseReceiptController;
use App\Http\Controllers\Api\Procurement\PurchaseReturnController;
use App\Http\Controllers\Api\Sales\QuoteController;
use App\Http\Controllers\Api\Sales\SalesOrderController;
use App\Http\Controllers\Api\Invoice\InvoiceController;
use App\Http\Controllers\Api\Payment\PaymentController;
use App\Http\Controllers\Api\POS\POSSessionController;
use App\Http\Controllers\Api\POS\POSTransactionController;
use App\Http\Controllers\Api\IAM\UserController;
use App\Http\Controllers\Api\IAM\RoleController;
use App\Http\Controllers\Api\IAM\PermissionController;
use App\Http\Controllers\Api\MasterData\CurrencyController;
use App\Http\Controllers\Api\MasterData\TaxRateController;
use App\Http\Controllers\Api\MasterData\UnitOfMeasureController;
use App\Http\Controllers\Api\MasterData\CountryController;
use App\Http\Controllers\Api\Manufacturing\BillOfMaterialController;
use App\Http\Controllers\Api\Manufacturing\WorkOrderController;
use App\Http\Controllers\Api\Warehouse\WarehouseTransferController;
use App\Http\Controllers\Api\Warehouse\WarehousePickingController;
use App\Http\Controllers\Api\Warehouse\WarehousePutawayController;
use App\Http\Controllers\Api\Taxation\TaxGroupController;
use App\Http\Controllers\Api\Taxation\TaxExemptionController;
use App\Http\Controllers\Api\Taxation\TaxJurisdictionController;
use App\Http\Controllers\Api\Taxation\TaxCalculationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PushSubscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => '1.0.0',
        ]);
    });
    
    // Authentication routes (public)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });
});

// Protected routes (authentication required)
Route::prefix('v1')->middleware(['auth:sanctum', 'tenant.context'])->group(function () {
    
    // Authentication routes (protected)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
    
    // IAM Routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/search', [UserController::class, 'search']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::post('/{id}/restore', [UserController::class, 'restore']);
        Route::post('/{id}/roles', [UserController::class, 'assignRoles']);
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
        Route::post('/{id}/permissions', [RoleController::class, 'assignPermissions']);
    });

    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::get('/by-module', [PermissionController::class, 'byModule']);
        Route::get('/{id}', [PermissionController::class, 'show']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
    });
    
    // Master Data Routes
    Route::prefix('master-data')->group(function () {
        // Currencies
        Route::prefix('currencies')->group(function () {
            Route::get('/', [CurrencyController::class, 'index']);
            Route::post('/', [CurrencyController::class, 'store']);
            Route::get('/active', [CurrencyController::class, 'active']);
            Route::get('/base', [CurrencyController::class, 'base']);
            Route::get('/{code}', [CurrencyController::class, 'show']);
            Route::put('/{code}', [CurrencyController::class, 'update']);
            Route::delete('/{code}', [CurrencyController::class, 'destroy']);
        });

        // Tax Rates
        Route::prefix('tax-rates')->group(function () {
            Route::get('/', [TaxRateController::class, 'index']);
            Route::post('/', [TaxRateController::class, 'store']);
            Route::get('/active', [TaxRateController::class, 'active']);
            Route::get('/valid-on', [TaxRateController::class, 'validOn']);
            Route::get('/{id}', [TaxRateController::class, 'show']);
            Route::put('/{id}', [TaxRateController::class, 'update']);
            Route::delete('/{id}', [TaxRateController::class, 'destroy']);
        });

        // Units of Measure
        Route::prefix('units')->group(function () {
            Route::get('/', [UnitOfMeasureController::class, 'index']);
            Route::post('/', [UnitOfMeasureController::class, 'store']);
            Route::get('/by-type/{type}', [UnitOfMeasureController::class, 'byType']);
            Route::get('/base-units', [UnitOfMeasureController::class, 'baseUnits']);
            Route::get('/{id}', [UnitOfMeasureController::class, 'show']);
            Route::put('/{id}', [UnitOfMeasureController::class, 'update']);
            Route::delete('/{id}', [UnitOfMeasureController::class, 'destroy']);
        });

        // Countries
        Route::prefix('countries')->group(function () {
            Route::get('/', [CountryController::class, 'index']);
            Route::get('/{code}', [CountryController::class, 'show']);
        });
    });
    
    // Product Management Routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/search', [ProductController::class, 'search']);
        Route::get('/low-stock', [ProductController::class, 'lowStock']);
        Route::get('/out-of-stock', [ProductController::class, 'outOfStock']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::post('/{id}/calculate-price', [ProductController::class, 'calculatePrice']);
    });

    // Inventory Management Routes
    Route::prefix('inventory')->group(function () {
        // Stock movements
        Route::post('/stock-in', [InventoryController::class, 'stockIn']);
        Route::post('/stock-out', [InventoryController::class, 'stockOut']);
        Route::post('/adjustment', [InventoryController::class, 'stockAdjustment']);
        Route::post('/transfer', [InventoryController::class, 'stockTransfer']);
        
        // Stock queries
        Route::get('/balance', [InventoryController::class, 'getCurrentBalance']);
        Route::get('/movements', [InventoryController::class, 'getMovements']);
        Route::get('/expiring-items', [InventoryController::class, 'getExpiringItems']);
        Route::get('/valuation', [InventoryController::class, 'calculateValuation']);
    });

    // CRM Routes
    Route::prefix('crm')->group(function () {
        // Customer routes
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index']);
            Route::post('/', [CustomerController::class, 'store']);
            Route::get('/search', [CustomerController::class, 'search']);
            Route::get('/statistics', [CustomerController::class, 'statistics']);
            Route::get('/{id}', [CustomerController::class, 'show']);
            Route::put('/{id}', [CustomerController::class, 'update']);
            Route::delete('/{id}', [CustomerController::class, 'destroy']);
        });

        // Contact routes
        Route::prefix('contacts')->group(function () {
            Route::get('/', [ContactController::class, 'index']);
            Route::post('/', [ContactController::class, 'store']);
            Route::get('/search', [ContactController::class, 'search']);
            Route::get('/{id}', [ContactController::class, 'show']);
            Route::put('/{id}', [ContactController::class, 'update']);
            Route::delete('/{id}', [ContactController::class, 'destroy']);
        });

        // Lead routes
        Route::prefix('leads')->group(function () {
            Route::get('/', [LeadController::class, 'index']);
            Route::post('/', [LeadController::class, 'store']);
            Route::get('/search', [LeadController::class, 'search']);
            Route::get('/statistics', [LeadController::class, 'statistics']);
            Route::get('/{id}', [LeadController::class, 'show']);
            Route::put('/{id}', [LeadController::class, 'update']);
            Route::delete('/{id}', [LeadController::class, 'destroy']);
            Route::post('/{id}/convert', [LeadController::class, 'convert']);
        });
    });

    // Procurement Routes
    Route::prefix('procurement')->group(function () {
        // Vendor routes
        Route::prefix('vendors')->group(function () {
            Route::get('/', [VendorController::class, 'index']);
            Route::post('/', [VendorController::class, 'store']);
            Route::get('/search', [VendorController::class, 'search']);
            Route::get('/statistics', [VendorController::class, 'statistics']);
            Route::get('/{id}', [VendorController::class, 'show']);
            Route::put('/{id}', [VendorController::class, 'update']);
            Route::delete('/{id}', [VendorController::class, 'destroy']);
        });

        // Purchase Order routes
        Route::prefix('purchase-orders')->group(function () {
            Route::get('/', [PurchaseOrderController::class, 'index']);
            Route::post('/', [PurchaseOrderController::class, 'store']);
            Route::get('/{id}', [PurchaseOrderController::class, 'show']);
            Route::put('/{id}', [PurchaseOrderController::class, 'update']);
            Route::delete('/{id}', [PurchaseOrderController::class, 'destroy']);
            Route::post('/{id}/approve', [PurchaseOrderController::class, 'approve']);
            Route::post('/{id}/reject', [PurchaseOrderController::class, 'reject']);
            Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
        });

        // Purchase Receipt routes (GRN)
        Route::prefix('purchase-receipts')->group(function () {
            Route::get('/', [PurchaseReceiptController::class, 'index']);
            Route::post('/', [PurchaseReceiptController::class, 'store']);
            Route::get('/{id}', [PurchaseReceiptController::class, 'show']);
            Route::delete('/{id}', [PurchaseReceiptController::class, 'destroy']);
            Route::post('/{id}/accept', [PurchaseReceiptController::class, 'accept']);
        });

        // Purchase Return routes
        Route::prefix('purchase-returns')->group(function () {
            Route::get('/', [PurchaseReturnController::class, 'index']);
            Route::post('/', [PurchaseReturnController::class, 'store']);
            Route::get('/{id}', [PurchaseReturnController::class, 'show']);
            Route::delete('/{id}', [PurchaseReturnController::class, 'destroy']);
            Route::post('/{id}/approve', [PurchaseReturnController::class, 'approve']);
        });
    });

    // Sales Routes
    Route::prefix('sales')->group(function () {
        // Quote routes
        Route::prefix('quotes')->group(function () {
            Route::get('/', [QuoteController::class, 'index']);
            Route::post('/', [QuoteController::class, 'store']);
            Route::get('/{id}', [QuoteController::class, 'show']);
            Route::put('/{id}', [QuoteController::class, 'update']);
            Route::delete('/{id}', [QuoteController::class, 'destroy']);
            Route::post('/{id}/convert', [QuoteController::class, 'convertToOrder']);
        });

        // Sales Order routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [SalesOrderController::class, 'index']);
            Route::post('/', [SalesOrderController::class, 'store']);
            Route::get('/{id}', [SalesOrderController::class, 'show']);
            Route::put('/{id}', [SalesOrderController::class, 'update']);
            Route::delete('/{id}', [SalesOrderController::class, 'destroy']);
            Route::post('/{id}/approve', [SalesOrderController::class, 'approve']);
            Route::post('/{id}/reserve-inventory', [SalesOrderController::class, 'reserveInventory']);
            Route::post('/from-quote/{quoteId}', [SalesOrderController::class, 'createFromQuote']);
        });
    });

    // Invoice Routes
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::put('/{id}', [InvoiceController::class, 'update']);
        Route::delete('/{id}', [InvoiceController::class, 'destroy']);
        Route::post('/{id}/approve', [InvoiceController::class, 'approve']);
        Route::post('/{id}/payments', [InvoiceController::class, 'recordPayment']);
        Route::post('/from-sales-order/{salesOrderId}', [InvoiceController::class, 'createFromSalesOrder']);
    });
    
    // Payment Management Routes
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/search', [PaymentController::class, 'search']);
        Route::get('/statistics', [PaymentController::class, 'statistics']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::put('/{id}', [PaymentController::class, 'update']);
        Route::delete('/{id}', [PaymentController::class, 'destroy']);
        Route::post('/{id}/reconcile', [PaymentController::class, 'reconcile']);
        Route::post('/{id}/unreconcile', [PaymentController::class, 'unreconcile']);
        Route::post('/{id}/complete', [PaymentController::class, 'complete']);
        Route::post('/{id}/cancel', [PaymentController::class, 'cancel']);
    });
    
    // POS Management Routes
    Route::prefix('pos')->group(function () {
        // POS Sessions
        Route::prefix('sessions')->group(function () {
            Route::get('/', [POSSessionController::class, 'index']);
            Route::post('/', [POSSessionController::class, 'store']);
            Route::get('/current', [POSSessionController::class, 'current']);
            Route::get('/{id}', [POSSessionController::class, 'show']);
            Route::post('/{id}/close', [POSSessionController::class, 'close']);
        });
        
        // POS Transactions
        Route::prefix('transactions')->group(function () {
            Route::get('/', [POSTransactionController::class, 'index']);
            Route::post('/', [POSTransactionController::class, 'store']);
            Route::get('/{id}', [POSTransactionController::class, 'show']);
            Route::post('/{id}/complete', [POSTransactionController::class, 'complete']);
            Route::post('/{id}/receipt', [POSTransactionController::class, 'generateReceipt']);
        });
    });
    
    // Pricing Management Routes
    Route::prefix('pricing')->group(function () {
        // Pricing Calculations
        Route::post('/calculate', [\App\Http\Controllers\Api\Pricing\PricingCalculationController::class, 'calculate']);
        Route::post('/calculate-bulk', [\App\Http\Controllers\Api\Pricing\PricingCalculationController::class, 'calculateBulk']);
        Route::get('/applicable-rules', [\App\Http\Controllers\Api\Pricing\PricingCalculationController::class, 'getApplicableRules']);
        Route::get('/applicable-tiers', [\App\Http\Controllers\Api\Pricing\PricingCalculationController::class, 'getApplicableTiers']);
        
        // Pricing Rules
        Route::prefix('rules')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Pricing\PricingRuleController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\Pricing\PricingRuleController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Pricing\PricingRuleController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\Pricing\PricingRuleController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\Pricing\PricingRuleController::class, 'destroy']);
            Route::post('/{id}/activate', [\App\Http\Controllers\Api\Pricing\PricingRuleController::class, 'activate']);
            Route::post('/{id}/deactivate', [\App\Http\Controllers\Api\Pricing\PricingRuleController::class, 'deactivate']);
        });
    });
    
    // Manufacturing Routes
    Route::prefix('manufacturing')->group(function () {
        // Bill of Materials (BOM)
        Route::prefix('boms')->group(function () {
            Route::get('/', [BillOfMaterialController::class, 'index']);
            Route::post('/', [BillOfMaterialController::class, 'store']);
            Route::get('/{id}', [BillOfMaterialController::class, 'show']);
            Route::put('/{id}', [BillOfMaterialController::class, 'update']);
            Route::delete('/{id}', [BillOfMaterialController::class, 'destroy']);
            Route::post('/{id}/activate', [BillOfMaterialController::class, 'activate']);
            Route::post('/{id}/deactivate', [BillOfMaterialController::class, 'deactivate']);
            Route::get('/{id}/calculate-materials', [BillOfMaterialController::class, 'calculateMaterials']);
            Route::get('/product/{productId}', [BillOfMaterialController::class, 'getByProduct']);
        });
        
        // Work Orders
        Route::prefix('work-orders')->group(function () {
            Route::get('/', [WorkOrderController::class, 'index']);
            Route::post('/', [WorkOrderController::class, 'store']);
            Route::get('/in-progress', [WorkOrderController::class, 'inProgress']);
            Route::get('/overdue', [WorkOrderController::class, 'overdue']);
            Route::get('/{id}', [WorkOrderController::class, 'show']);
            Route::put('/{id}', [WorkOrderController::class, 'update']);
            Route::delete('/{id}', [WorkOrderController::class, 'destroy']);
            Route::post('/{id}/start-production', [WorkOrderController::class, 'startProduction']);
            Route::post('/{id}/complete-production', [WorkOrderController::class, 'completeProduction']);
            Route::post('/{id}/cancel', [WorkOrderController::class, 'cancel']);
        });
    });

    // Warehouse Management Routes
    Route::prefix('warehouse')->group(function () {
        // Warehouse Transfer routes
        Route::prefix('transfers')->group(function () {
            Route::get('/', [WarehouseTransferController::class, 'index']);
            Route::post('/', [WarehouseTransferController::class, 'store']);
            Route::get('/pending', [WarehouseTransferController::class, 'pending']);
            Route::get('/in-transit', [WarehouseTransferController::class, 'inTransit']);
            Route::get('/{id}', [WarehouseTransferController::class, 'show']);
            Route::put('/{id}', [WarehouseTransferController::class, 'update']);
            Route::delete('/{id}', [WarehouseTransferController::class, 'destroy']);
            Route::post('/{id}/approve', [WarehouseTransferController::class, 'approve']);
            Route::post('/{id}/ship', [WarehouseTransferController::class, 'ship']);
            Route::post('/{id}/receive', [WarehouseTransferController::class, 'receive']);
            Route::post('/{id}/cancel', [WarehouseTransferController::class, 'cancel']);
        });

        // Warehouse Picking routes
        Route::prefix('pickings')->group(function () {
            Route::get('/', [WarehousePickingController::class, 'index']);
            Route::post('/', [WarehousePickingController::class, 'store']);
            Route::get('/pending', [WarehousePickingController::class, 'pending']);
            Route::get('/efficiency', [WarehousePickingController::class, 'efficiency']);
            Route::get('/{id}', [WarehousePickingController::class, 'show']);
            Route::delete('/{id}', [WarehousePickingController::class, 'destroy']);
            Route::post('/{id}/assign', [WarehousePickingController::class, 'assign']);
            Route::post('/{id}/start', [WarehousePickingController::class, 'start']);
            Route::post('/{id}/pick', [WarehousePickingController::class, 'pick']);
            Route::post('/{id}/complete', [WarehousePickingController::class, 'complete']);
            Route::post('/{id}/cancel', [WarehousePickingController::class, 'cancel']);
        });

        // Warehouse Putaway routes
        Route::prefix('putaways')->group(function () {
            Route::get('/', [WarehousePutawayController::class, 'index']);
            Route::post('/', [WarehousePutawayController::class, 'store']);
            Route::get('/pending', [WarehousePutawayController::class, 'pending']);
            Route::get('/{id}', [WarehousePutawayController::class, 'show']);
            Route::delete('/{id}', [WarehousePutawayController::class, 'destroy']);
            Route::post('/{id}/assign', [WarehousePutawayController::class, 'assign']);
            Route::post('/{id}/start', [WarehousePutawayController::class, 'start']);
            Route::post('/{id}/putaway', [WarehousePutawayController::class, 'putaway']);
            Route::post('/{id}/complete', [WarehousePutawayController::class, 'complete']);
            Route::post('/{id}/cancel', [WarehousePutawayController::class, 'cancel']);
        });
    });

    // Taxation Routes
    Route::prefix('taxation')->group(function () {
        // Tax Groups
        Route::prefix('tax-groups')->group(function () {
            Route::get('/', [TaxGroupController::class, 'index']);
            Route::post('/', [TaxGroupController::class, 'store']);
            Route::get('/active', [TaxGroupController::class, 'active']);
            Route::get('/{id}', [TaxGroupController::class, 'show']);
            Route::put('/{id}', [TaxGroupController::class, 'update']);
            Route::delete('/{id}', [TaxGroupController::class, 'destroy']);
            Route::post('/{id}/attach-tax-rate', [TaxGroupController::class, 'attachTaxRate']);
            Route::delete('/{id}/detach-tax-rate/{taxRateId}', [TaxGroupController::class, 'detachTaxRate']);
        });

        // Tax Exemptions
        Route::prefix('tax-exemptions')->group(function () {
            Route::get('/', [TaxExemptionController::class, 'index']);
            Route::post('/', [TaxExemptionController::class, 'store']);
            Route::get('/active', [TaxExemptionController::class, 'active']);
            Route::get('/by-entity', [TaxExemptionController::class, 'byEntity']);
            Route::get('/{id}', [TaxExemptionController::class, 'show']);
            Route::put('/{id}', [TaxExemptionController::class, 'update']);
            Route::delete('/{id}', [TaxExemptionController::class, 'destroy']);
        });

        // Tax Jurisdictions
        Route::prefix('tax-jurisdictions')->group(function () {
            Route::get('/', [TaxJurisdictionController::class, 'index']);
            Route::post('/', [TaxJurisdictionController::class, 'store']);
            Route::get('/active', [TaxJurisdictionController::class, 'active']);
            Route::get('/find-by-location', [TaxJurisdictionController::class, 'findByLocation']);
            Route::get('/{id}', [TaxJurisdictionController::class, 'show']);
            Route::put('/{id}', [TaxJurisdictionController::class, 'update']);
            Route::delete('/{id}', [TaxJurisdictionController::class, 'destroy']);
        });

        // Tax Calculations
        Route::prefix('calculations')->group(function () {
            Route::post('/calculate', [TaxCalculationController::class, 'calculate']);
            Route::post('/calculate-and-save', [TaxCalculationController::class, 'calculateAndSave']);
            Route::get('/history', [TaxCalculationController::class, 'history']);
            Route::get('/summary', [TaxCalculationController::class, 'summary']);
            Route::get('/breakdown', [TaxCalculationController::class, 'breakdown']);
        });
    });

    // Notification Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{notificationId}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::get('/preferences', [NotificationController::class, 'getPreferences']);
        Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
    });

    // Push Notification Routes
    Route::prefix('push')->group(function () {
        Route::get('/public-key', [PushSubscriptionController::class, 'getPublicKey']);
        Route::post('/subscribe', [PushSubscriptionController::class, 'subscribe']);
        Route::post('/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
        Route::post('/test', [PushSubscriptionController::class, 'test']);
    });
});
