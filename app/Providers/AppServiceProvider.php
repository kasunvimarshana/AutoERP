<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ApTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\ArTransactionModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\CostCenterModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollRunModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountHeaderModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseInvoiceModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesInvoiceModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\ShipmentModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolymorphicMaps();
    }

    /**
     * Register polymorphic morph maps for type resolution and validation.
     *
     * These maps constrain polymorphic relationships to specific model types,
     * improving type safety and enabling lazy-eager loading.
     */
    private function registerPolymorphicMaps(): void
    {
        Relation::enforceMorphMap([
            // Finance: Invoice types for PaymentAllocation
            'sales_invoice' => SalesInvoiceModel::class,
            'purchase_invoice' => PurchaseInvoiceModel::class,

            // Finance: Reference types for ArTransaction, ApTransaction, JournalEntry
            'sales_order' => SalesOrderModel::class,
            'purchase_order' => PurchaseOrderModel::class,
            'grn' => GrnHeaderModel::class,
            'shipment' => ShipmentModel::class,
            'payment' => PaymentModel::class,
            // Backward-compatible reference aliases used by existing flows/tests
            'purchase_payment' => PaymentModel::class,
            'sales_payment' => PaymentModel::class,
            'purchase_return' => PurchaseReturnModel::class,
            'sales_return' => SalesReturnModel::class,
            'stock_movement' => StockMovementModel::class,
            'payroll_run' => PayrollRunModel::class,
            'cycle_count_headers' => CycleCountHeaderModel::class,

            // Inventory: Reference types for StockMovement, InventoryCostLayer
            'cycle_count' => CycleCountHeaderModel::class,
            'transfer_order' => TransferOrderModel::class,

            // Inventory: ReservedFor types for StockReservation
            'sales_order_line' => SalesOrderLineModel::class,
            'sales_order_lines' => SalesOrderLineModel::class,

            // Inventory: CurrentOwner types for Serial
            'customer' => CustomerModel::class,
            'supplier' => SupplierModel::class,
            'employee' => EmployeeModel::class,
            'warehouse_location' => WarehouseLocationModel::class,

            // Inventory: Entity types for TraceLog
            'product' => ProductModel::class,
            'product_variant' => ProductVariantModel::class,
            'batch' => BatchModel::class,
            'serial' => SerialModel::class,
            'warehouse_location_entity' => WarehouseLocationModel::class,

            // Audit: Auditable types
            'user' => UserModel::class,
            'tenant' => TenantModel::class,
            'org_unit' => OrganizationUnitModel::class,
            'account' => AccountModel::class,
            'cost_center' => CostCenterModel::class,
            'journal_entry' => JournalEntryModel::class,
            'ar_transaction' => ArTransactionModel::class,
            'ap_transaction' => ApTransactionModel::class,
            'stock_level' => StockLevelModel::class,
            'stock_reservation' => StockReservationModel::class,
        ]);
    }
}
