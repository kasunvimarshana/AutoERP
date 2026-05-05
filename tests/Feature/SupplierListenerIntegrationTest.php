<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Domain\Events\GoodsReceiptPosted;
use Modules\Supplier\Infrastructure\Listeners\HandleGoodsReceiptPosted;
use Tests\TestCase;

/**
 * Integration tests for the Supplier module's HandleGoodsReceiptPosted listener.
 *
 * Verifies that supplier_products.last_purchase_price is updated correctly
 * when a GoodsReceiptPosted event is fired.
 */
class SupplierListenerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $supplierId = 1;

    private int $productId = 1;

    private int $warehouseId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_handle_goods_receipt_posted_updates_last_purchase_price(): void
    {
        DB::table('supplier_products')->insert([
            'tenant_id'           => $this->tenantId,
            'org_unit_id'         => null,
            'row_version'         => 1,
            'supplier_id'         => $this->supplierId,
            'product_id'          => $this->productId,
            'variant_id'          => null,
            'last_purchase_price' => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $event = new GoodsReceiptPosted(
            tenantId: $this->tenantId,
            grnHeaderId: 10,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            lines: [
                [
                    'id'          => null,
                    'product_id'  => $this->productId,
                    'location_id' => 1,
                    'uom_id'      => 1,
                    'received_qty' => '5.000000',
                    'unit_cost'   => '125.500000',
                    'variant_id'  => null,
                    'batch_id'    => null,
                    'serial_id'   => null,
                ],
            ],
        );

        (new HandleGoodsReceiptPosted())->handle($event);

        $row = DB::table('supplier_products')
            ->where('tenant_id', $this->tenantId)
            ->where('supplier_id', $this->supplierId)
            ->where('product_id', $this->productId)
            ->whereNull('variant_id')
            ->first();

        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(125.5, (float) $row->last_purchase_price, 0.0001);
    }

    public function test_handle_goods_receipt_posted_updates_correct_variant(): void
    {
        DB::table('supplier_products')->insert([
            [
                'tenant_id'           => $this->tenantId,
                'org_unit_id'         => null,
                'row_version'         => 1,
                'supplier_id'         => $this->supplierId,
                'product_id'          => $this->productId,
                'variant_id'          => null,
                'last_purchase_price' => '50.000000',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'tenant_id'           => $this->tenantId,
                'org_unit_id'         => null,
                'row_version'         => 1,
                'supplier_id'         => $this->supplierId,
                'product_id'          => $this->productId,
                'variant_id'          => 1,
                'last_purchase_price' => '60.000000',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);

        $event = new GoodsReceiptPosted(
            tenantId: $this->tenantId,
            grnHeaderId: 11,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            lines: [
                [
                    'id'          => null,
                    'product_id'  => $this->productId,
                    'location_id' => 1,
                    'uom_id'      => 1,
                    'received_qty' => '2.000000',
                    'unit_cost'   => '75.000000',
                    'variant_id'  => 1,
                    'batch_id'    => null,
                    'serial_id'   => null,
                ],
            ],
        );

        (new HandleGoodsReceiptPosted())->handle($event);

        // Variant row should be updated
        $variantRow = DB::table('supplier_products')
            ->where('tenant_id', $this->tenantId)
            ->where('supplier_id', $this->supplierId)
            ->where('product_id', $this->productId)
            ->where('variant_id', 1)
            ->first();

        $this->assertEqualsWithDelta(75.0, (float) $variantRow->last_purchase_price, 0.0001);

        // Base product (no variant) should remain unchanged
        $baseRow = DB::table('supplier_products')
            ->where('tenant_id', $this->tenantId)
            ->where('supplier_id', $this->supplierId)
            ->where('product_id', $this->productId)
            ->whereNull('variant_id')
            ->first();

        $this->assertEqualsWithDelta(50.0, (float) $baseRow->last_purchase_price, 0.0001);
    }

    public function test_handle_goods_receipt_posted_silently_skips_unregistered_products(): void
    {
        // No supplier_products row exists — listener must not throw or insert
        $event = new GoodsReceiptPosted(
            tenantId: $this->tenantId,
            grnHeaderId: 12,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            lines: [
                [
                    'id'          => null,
                    'product_id'  => 999,
                    'location_id' => 1,
                    'uom_id'      => 1,
                    'received_qty' => '1.000000',
                    'unit_cost'   => '100.000000',
                    'variant_id'  => null,
                    'batch_id'    => null,
                    'serial_id'   => null,
                ],
            ],
        );

        (new HandleGoodsReceiptPosted())->handle($event);

        $this->assertSame(0, DB::table('supplier_products')->count());
    }

    public function test_handle_goods_receipt_posted_skips_when_no_lines(): void
    {
        DB::table('supplier_products')->insert([
            'tenant_id'           => $this->tenantId,
            'org_unit_id'         => null,
            'row_version'         => 1,
            'supplier_id'         => $this->supplierId,
            'product_id'          => $this->productId,
            'variant_id'          => null,
            'last_purchase_price' => '99.000000',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $event = new GoodsReceiptPosted(
            tenantId: $this->tenantId,
            grnHeaderId: 13,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            lines: [],
        );

        (new HandleGoodsReceiptPosted())->handle($event);

        $row = DB::table('supplier_products')
            ->where('tenant_id', $this->tenantId)
            ->where('supplier_id', $this->supplierId)
            ->where('product_id', $this->productId)
            ->first();

        // Price should be unchanged
        $this->assertEqualsWithDelta(99.0, (float) $row->last_purchase_price, 0.0001);
    }

    public function test_handle_goods_receipt_posted_processes_multiple_lines(): void
    {
        DB::table('supplier_products')->insert([
            [
                'tenant_id'           => $this->tenantId,
                'org_unit_id'         => null,
                'row_version'         => 1,
                'supplier_id'         => $this->supplierId,
                'product_id'          => $this->productId,
                'variant_id'          => null,
                'last_purchase_price' => null,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'tenant_id'           => $this->tenantId,
                'org_unit_id'         => null,
                'row_version'         => 1,
                'supplier_id'         => $this->supplierId,
                'product_id'          => 2,
                'variant_id'          => null,
                'last_purchase_price' => null,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);

        $event = new GoodsReceiptPosted(
            tenantId: $this->tenantId,
            grnHeaderId: 14,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            lines: [
                [
                    'id'          => null,
                    'product_id'  => $this->productId,
                    'location_id' => 1,
                    'uom_id'      => 1,
                    'received_qty' => '3.000000',
                    'unit_cost'   => '10.000000',
                    'variant_id'  => null,
                    'batch_id'    => null,
                    'serial_id'   => null,
                ],
                [
                    'id'          => null,
                    'product_id'  => 2,
                    'location_id' => 1,
                    'uom_id'      => 1,
                    'received_qty' => '1.000000',
                    'unit_cost'   => '20.000000',
                    'variant_id'  => null,
                    'batch_id'    => null,
                    'serial_id'   => null,
                ],
            ],
        );

        (new HandleGoodsReceiptPosted())->handle($event);

        $this->assertEqualsWithDelta(
            10.0,
            (float) DB::table('supplier_products')
                ->where('product_id', $this->productId)
                ->whereNull('variant_id')
                ->value('last_purchase_price'),
            0.0001,
        );

        $this->assertEqualsWithDelta(
            20.0,
            (float) DB::table('supplier_products')
                ->where('product_id', 2)
                ->whereNull('variant_id')
                ->value('last_purchase_price'),
            0.0001,
        );
    }

    private function seedReferenceData(): void
    {
        DB::table('tenants')->insert([
            'id'         => $this->tenantId,
            'name'       => 'Test Tenant',
            'slug'       => 'test-tenant',
            'domain'     => null,
            'plan'       => 'free',
            'status'     => 'active',
            'active'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('units_of_measure')->insert([
            'id'          => 1,
            'tenant_id'   => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'name'        => 'Each',
            'symbol'      => 'ea',
            'type'        => 'unit',
            'is_base'     => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('products')->insert([
            [
                'id'            => $this->productId,
                'tenant_id'     => $this->tenantId,
                'org_unit_id'   => null,
                'row_version'   => 1,
                'name'          => 'Product A',
                'slug'          => 'product-a',
                'sku'           => 'SKU-A',
                'type'          => 'physical',
                'base_uom_id'   => 1,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => 2,
                'tenant_id'     => $this->tenantId,
                'org_unit_id'   => null,
                'row_version'   => 1,
                'name'          => 'Product B',
                'slug'          => 'product-b',
                'sku'           => 'SKU-B',
                'type'          => 'physical',
                'base_uom_id'   => 1,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);

        DB::table('product_variants')->insert([
            'id'          => 1,
            'tenant_id'   => $this->tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'product_id'  => $this->productId,
            'sku'         => 'SKU-A-VAR1',
            'name'        => 'Product A Variant 1',
            'is_default'  => false,
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('suppliers')->insert([
            'id'                  => $this->supplierId,
            'tenant_id'           => $this->tenantId,
            'org_unit_id'         => null,
            'row_version'         => 1,
            'supplier_code'       => 'SUP001',
            'name'                => 'Test Supplier',
            'type'                => 'company',
            'currency_id'         => null,
            'payment_terms_days'  => 30,
            'ap_account_id'       => null,
            'status'              => 'active',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }
}
