<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class AuditTraitTest extends TestCase
{
    private function assertModelUsesHasAudit(string $class): void
    {
        $this->assertContains(
            HasAudit::class,
            class_uses_recursive($class),
            "{$class} should use HasAudit"
        );
    }

    public function test_inventory_level_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryLevelModel::class
        );
    }

    public function test_goods_receipt_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptModel::class
        );
    }

    public function test_product_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel::class
        );
    }

    public function test_customer_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel::class
        );
    }

    public function test_supplier_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel::class
        );
    }

    public function test_purchase_order_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel::class
        );
    }

    public function test_sales_order_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel::class
        );
    }

    public function test_stock_return_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnModel::class
        );
    }

    public function test_user_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel::class
        );
    }

    public function test_warehouse_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel::class
        );
    }

    public function test_uom_conversion_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel::class
        );
    }

    public function test_stock_movement_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models\StockMovementModel::class
        );
    }

    public function test_dispatch_model_uses_has_audit(): void
    {
        $this->assertModelUsesHasAudit(
            \Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchModel::class
        );
    }
}
