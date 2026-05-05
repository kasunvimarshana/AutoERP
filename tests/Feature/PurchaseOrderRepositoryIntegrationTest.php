<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Tests\TestCase;

class PurchaseOrderRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $currencyId = 1;

    private int $warehouseId = 1;

    private int $supplierId = 1;

    private int $createdBy = 1;

    private int $productId = 1;

    private int $uomId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_save_creates_a_new_purchase_order(): void
    {
        /** @var PurchaseOrderRepositoryInterface $repository */
        $repository = app(PurchaseOrderRepositoryInterface::class);

        $order = new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-TEST-001',
            status: 'draft',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-01-15'),
            createdBy: $this->createdBy,
            subtotal: '200.000000',
            grandTotal: '200.000000',
        );

        $saved = $repository->save($order);

        $this->assertNotNull($saved->getId());
        $this->assertSame($this->tenantId, $saved->getTenantId());
        $this->assertSame($this->supplierId, $saved->getSupplierId());
        $this->assertSame('PO-TEST-001', $saved->getPoNumber());
        $this->assertSame('draft', $saved->getStatus());
        $this->assertSame('200.000000', $saved->getSubtotal());
        $this->assertSame('200.000000', $saved->getGrandTotal());
    }

    public function test_save_updates_existing_purchase_order(): void
    {
        /** @var PurchaseOrderRepositoryInterface $repository */
        $repository = app(PurchaseOrderRepositoryInterface::class);

        $created = $repository->save(new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-UPDATE-001',
            status: 'draft',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-01-15'),
            createdBy: $this->createdBy,
        ));

        $this->assertNotNull($created->getId());

        $updated = new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-UPDATE-001',
            status: 'confirmed',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-01-16'),
            createdBy: $this->createdBy,
            subtotal: '500.000000',
            grandTotal: '500.000000',
            id: $created->getId(),
        );

        $saved = $repository->save($updated);

        $this->assertSame($created->getId(), $saved->getId());
        $this->assertSame('confirmed', $saved->getStatus());
        $this->assertSame('500.000000', $saved->getSubtotal());
    }

    public function test_find_returns_purchase_order(): void
    {
        /** @var PurchaseOrderRepositoryInterface $repository */
        $repository = app(PurchaseOrderRepositoryInterface::class);

        $saved = $repository->save(new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-FIND-001',
            status: 'draft',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-03-01'),
            createdBy: $this->createdBy,
        ));

        $found = $repository->find($saved->getId());

        $this->assertInstanceOf(PurchaseOrder::class, $found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('PO-FIND-001', $found->getPoNumber());
        $this->assertSame($this->tenantId, $found->getTenantId());
        $this->assertSame($this->supplierId, $found->getSupplierId());
    }

    public function test_find_returns_null_for_missing_order(): void
    {
        /** @var PurchaseOrderRepositoryInterface $repository */
        $repository = app(PurchaseOrderRepositoryInterface::class);

        $result = $repository->find(99999);

        $this->assertNull($result);
    }

    public function test_update_service_rejects_cross_tenant_purchase_order_mutation(): void
    {
        /** @var PurchaseOrderRepositoryInterface $repository */
        $repository = app(PurchaseOrderRepositoryInterface::class);
        /** @var UpdatePurchaseOrderServiceInterface $updateService */
        $updateService = app(UpdatePurchaseOrderServiceInterface::class);

        $created = $repository->save(new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-CROSS-001',
            status: 'draft',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-04-01'),
            createdBy: $this->createdBy,
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $updateService->execute([
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId + 1,
                'supplier_id' => $this->supplierId,
                'warehouse_id' => $this->warehouseId,
                'po_number' => 'PO-CROSS-UPDATED',
                'currency_id' => $this->currencyId,
                'order_date' => '2026-04-02',
                'created_by' => $this->createdBy,
            ]);

            $this->fail('Expected cross-tenant purchase order update to be rejected.');
        } catch (PurchaseOrderNotFoundException) {
            $this->assertDatabaseHas('purchase_orders', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'po_number' => 'PO-CROSS-001',
                'status' => 'draft',
            ]);
        }
    }

    public function test_delete_service_rejects_cross_tenant_purchase_order_mutation(): void
    {
        /** @var PurchaseOrderRepositoryInterface $repository */
        $repository = app(PurchaseOrderRepositoryInterface::class);
        /** @var DeletePurchaseOrderServiceInterface $deleteService */
        $deleteService = app(DeletePurchaseOrderServiceInterface::class);

        $created = $repository->save(new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-DELETE-CROSS-001',
            status: 'draft',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-04-05'),
            createdBy: $this->createdBy,
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $deleteService->execute([
                'id' => $created->getId(),
            ]);

            $this->fail('Expected cross-tenant purchase order delete to be rejected.');
        } catch (PurchaseOrderNotFoundException) {
            $this->assertDatabaseHas('purchase_orders', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'po_number' => 'PO-DELETE-CROSS-001',
                'status' => 'draft',
            ]);
        }
    }

    public function test_purchase_order_status_transitions(): void
    {
        $order = new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-STATUS-001',
            status: 'draft',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-05-01'),
            createdBy: $this->createdBy,
        );

        $this->assertSame('draft', $order->getStatus());

        $order->confirm();
        $this->assertSame('confirmed', $order->getStatus());

        $order->markPartial();
        $this->assertSame('partial', $order->getStatus());

        $order->receive();
        $this->assertSame('received', $order->getStatus());

        $order->close();
        $this->assertSame('closed', $order->getStatus());
    }

    public function test_purchase_order_cancel(): void
    {
        $order = new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-CANCEL-001',
            status: 'draft',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-06-01'),
            createdBy: $this->createdBy,
        );

        $order->cancel();
        $this->assertSame('cancelled', $order->getStatus());
    }

    public function test_save_with_purchase_order_lines(): void
    {
        /** @var PurchaseOrderRepositoryInterface $repository */
        $repository = app(PurchaseOrderRepositoryInterface::class);

        $order = new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-LINES-001',
            status: 'draft',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-02-01'),
            createdBy: $this->createdBy,
            subtotal: '150.000000',
            grandTotal: '150.000000',
        );

        $saved = $repository->save($order);

        $this->assertNotNull($saved->getId());

        DB::table('purchase_order_lines')->insert([
            'tenant_id' => $this->tenantId,
            'purchase_order_id' => $saved->getId(),
            'product_id' => $this->productId,
            'uom_id' => $this->uomId,
            'ordered_qty' => '10.000000',
            'received_qty' => '0.000000',
            'unit_price' => '15.000000',
            'discount_pct' => '0.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lineCount = DB::table('purchase_order_lines')
            ->where('purchase_order_id', $saved->getId())
            ->count();

        $this->assertSame(1, $lineCount);
    }

    private function seedReferenceData(): void
    {
        DB::table('tenants')->insert([
            'id' => $this->tenantId,
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('currencies')->insert([
            'id' => $this->currencyId,
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => $this->createdBy,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('suppliers')->insert([
            'id' => $this->supplierId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'user_id' => null,
            'supplier_code' => 'SUP-001',
            'name' => 'Test Supplier',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => null,
            'payment_terms_days' => 30,
            'ap_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => $this->warehouseId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'name' => 'Main Warehouse',
            'code' => 'WH-001',
            'image_path' => null,
            'type' => 'standard',
            'address_id' => null,
            'is_active' => true,
            'is_default' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('units_of_measure')->insert([
            'id' => $this->uomId,
            'tenant_id' => $this->tenantId,
            'name' => 'Each',
            'symbol' => 'EA',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => $this->productId,
            'tenant_id' => $this->tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-SKU-001',
            'description' => null,
            'image_path' => null,
            'base_uom_id' => $this->uomId,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => 1,
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
