<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Entities\SalesOrderLine;
use Modules\Sales\Domain\Exceptions\SalesOrderNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Tests\TestCase;

class SalesOrderRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $currencyId = 1;

    private int $warehouseId = 1;

    private int $customerId = 1;

    private int $createdBy = 1;

    private int $productId = 1;

    private int $uomId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_save_creates_a_new_sales_order(): void
    {
        /** @var SalesOrderRepositoryInterface $repository */
        $repository = app(SalesOrderRepositoryInterface::class);

        $order = new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-01-15'),
            soNumber: 'SO-TEST-001',
            status: 'draft',
            subtotal: '100.000000',
            grandTotal: '100.000000',
            createdBy: $this->createdBy,
        );

        $saved = $repository->save($order);

        $this->assertNotNull($saved->getId());
        $this->assertSame($this->tenantId, $saved->getTenantId());
        $this->assertSame($this->customerId, $saved->getCustomerId());
        $this->assertSame('SO-TEST-001', $saved->getSoNumber());
        $this->assertSame('draft', $saved->getStatus());
        $this->assertSame('100.000000', $saved->getSubtotal());
        $this->assertSame('100.000000', $saved->getGrandTotal());
    }

    public function test_save_updates_existing_sales_order(): void
    {
        /** @var SalesOrderRepositoryInterface $repository */
        $repository = app(SalesOrderRepositoryInterface::class);

        $created = $repository->save(new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-01-15'),
            soNumber: 'SO-UPDATE-001',
            status: 'draft',
            createdBy: $this->createdBy,
        ));

        $this->assertNotNull($created->getId());

        $updated = new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-01-16'),
            soNumber: 'SO-UPDATE-001',
            status: 'confirmed',
            subtotal: '250.000000',
            grandTotal: '250.000000',
            createdBy: $this->createdBy,
            id: $created->getId(),
        );

        $saved = $repository->save($updated);

        $this->assertSame($created->getId(), $saved->getId());
        $this->assertSame('confirmed', $saved->getStatus());
        $this->assertSame('250.000000', $saved->getSubtotal());
    }

    public function test_save_replaces_sales_order_lines(): void
    {
        /** @var SalesOrderRepositoryInterface $repository */
        $repository = app(SalesOrderRepositoryInterface::class);

        $order = new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-02-01'),
            soNumber: 'SO-LINES-001',
            status: 'draft',
            createdBy: $this->createdBy,
        );

        $line = new SalesOrderLine(
            tenantId: $this->tenantId,
            productId: $this->productId,
            uomId: $this->uomId,
            orderedQty: '5.000000',
            unitPrice: '20.000000',
            lineTotal: '100.000000',
        );
        $order->setLines([$line]);

        $saved = $repository->save($order);

        $this->assertNotNull($saved->getId());
        $this->assertCount(1, $saved->getLines());
        $this->assertSame('5.000000', $saved->getLines()[0]->getOrderedQty());
        $this->assertSame('20.000000', $saved->getLines()[0]->getUnitPrice());
    }

    public function test_find_returns_sales_order_with_lines(): void
    {
        /** @var SalesOrderRepositoryInterface $repository */
        $repository = app(SalesOrderRepositoryInterface::class);

        $order = new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-03-01'),
            soNumber: 'SO-FIND-001',
            status: 'draft',
            createdBy: $this->createdBy,
        );
        $order->setLines([
            new SalesOrderLine(
                tenantId: $this->tenantId,
                productId: $this->productId,
                uomId: $this->uomId,
                orderedQty: '3.000000',
                unitPrice: '10.000000',
                lineTotal: '30.000000',
            ),
        ]);

        $saved = $repository->save($order);
        $found = $repository->find($saved->getId());

        $this->assertInstanceOf(SalesOrder::class, $found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('SO-FIND-001', $found->getSoNumber());
        $this->assertCount(1, $found->getLines());
        $this->assertSame('3.000000', $found->getLines()[0]->getOrderedQty());
    }

    public function test_find_returns_null_for_missing_order(): void
    {
        /** @var SalesOrderRepositoryInterface $repository */
        $repository = app(SalesOrderRepositoryInterface::class);

        $result = $repository->find(99999);

        $this->assertNull($result);
    }

    public function test_find_by_tenant_and_so_number(): void
    {
        /** @var SalesOrderRepositoryInterface $repository */
        $repository = app(SalesOrderRepositoryInterface::class);

        $repository->save(new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-04-01'),
            soNumber: 'SO-LOOKUP-001',
            status: 'draft',
            createdBy: $this->createdBy,
        ));

        $found = $repository->findByTenantAndSoNumber($this->tenantId, 'SO-LOOKUP-001');

        $this->assertInstanceOf(SalesOrder::class, $found);
        $this->assertSame('SO-LOOKUP-001', $found->getSoNumber());
        $this->assertSame($this->tenantId, $found->getTenantId());
    }

    public function test_find_by_tenant_and_so_number_returns_null_when_not_found(): void
    {
        /** @var SalesOrderRepositoryInterface $repository */
        $repository = app(SalesOrderRepositoryInterface::class);

        $result = $repository->findByTenantAndSoNumber($this->tenantId, 'NONEXISTENT-SO');

        $this->assertNull($result);
    }

    public function test_update_service_rejects_cross_tenant_sales_order_mutation(): void
    {
        /** @var SalesOrderRepositoryInterface $repository */
        $repository = app(SalesOrderRepositoryInterface::class);
        /** @var UpdateSalesOrderServiceInterface $updateService */
        $updateService = app(UpdateSalesOrderServiceInterface::class);

        $created = $repository->save(new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-04-01'),
            soNumber: 'SO-CROSS-001',
            status: 'draft',
            createdBy: $this->createdBy,
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $updateService->execute([
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId + 1,
                'customer_id' => $this->customerId,
                'warehouse_id' => $this->warehouseId,
                'currency_id' => $this->currencyId,
                'order_date' => '2026-04-02',
                'so_number' => 'SO-CROSS-UPDATED',
                'created_by' => $this->createdBy,
            ]);

            $this->fail('Expected cross-tenant sales order update to be rejected.');
        } catch (SalesOrderNotFoundException) {
            $this->assertDatabaseHas('sales_orders', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'so_number' => 'SO-CROSS-001',
                'status' => 'draft',
            ]);
        }
    }

    public function test_cancel_service_rejects_cross_tenant_sales_order_mutation(): void
    {
        /** @var SalesOrderRepositoryInterface $repository */
        $repository = app(SalesOrderRepositoryInterface::class);
        /** @var CancelSalesOrderServiceInterface $cancelService */
        $cancelService = app(CancelSalesOrderServiceInterface::class);

        $created = $repository->save(new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-04-05'),
            soNumber: 'SO-CANCEL-CROSS-001',
            status: 'draft',
            createdBy: $this->createdBy,
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $cancelService->execute([
                'id' => $created->getId(),
            ]);

            $this->fail('Expected cross-tenant sales order cancel to be rejected.');
        } catch (SalesOrderNotFoundException) {
            $this->assertDatabaseHas('sales_orders', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'so_number' => 'SO-CANCEL-CROSS-001',
                'status' => 'draft',
            ]);
        }
    }

    public function test_sales_order_status_transitions(): void
    {
        $order = new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-05-01'),
            soNumber: 'SO-STATUS-001',
            status: 'draft',
            createdBy: $this->createdBy,
        );

        $this->assertSame('draft', $order->getStatus());

        $order->confirm();
        $this->assertSame('confirmed', $order->getStatus());

        $order->markShipped();
        $this->assertSame('shipped', $order->getStatus());

        $order->markInvoiced();
        $this->assertSame('invoiced', $order->getStatus());

        $order->close();
        $this->assertSame('closed', $order->getStatus());
    }

    public function test_sales_order_cancel_from_draft(): void
    {
        $order = new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-06-01'),
            soNumber: 'SO-CANCEL-001',
            status: 'draft',
            createdBy: $this->createdBy,
        );

        $order->cancel();
        $this->assertSame('cancelled', $order->getStatus());
    }

    public function test_sales_order_cannot_cancel_when_shipped(): void
    {
        $order = new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-07-01'),
            soNumber: 'SO-NOCANCEL-001',
            status: 'shipped',
            createdBy: $this->createdBy,
        );

        $this->expectException(\InvalidArgumentException::class);
        $order->cancel();
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

        DB::table('customers')->insert([
            'id' => $this->customerId,
            'tenant_id' => $this->tenantId,
            'user_id' => null,
            'org_unit_id' => null,
            'customer_code' => 'CUST-001',
            'name' => 'Test Customer',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => $this->currencyId,
            'credit_limit' => 0,
            'payment_terms_days' => 30,
            'ar_account_id' => null,
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
