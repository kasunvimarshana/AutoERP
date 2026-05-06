<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Tests\TestCase;

class ProductVariantRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAccountsTableExists();
        $this->seedReferenceData();
    }

    public function test_save_creates_and_updates_product_variant(): void
    {
        /** @var ProductVariantRepositoryInterface $repository */
        $repository = app(ProductVariantRepositoryInterface::class);

        $created = $repository->save(new ProductVariant(
            productId: 8001,
            tenantId: 81,
            orgUnitId: null,
            name: 'Blue Variant',
            sku: 'BLU-001',
            isDefault: false,
            isActive: true,
            purchasePrice: '7.500000',
            salesPrice: '15.250000',
            metadata: ['color' => 'blue'],
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('Blue Variant', $created->getName());
        $this->assertSame('7.500000', $created->getPurchasePrice());
        $this->assertSame('15.250000', $created->getSalesPrice());

        $updated = $repository->save(new ProductVariant(
            id: $created->getId(),
            productId: 8001,
            tenantId: 81,
            orgUnitId: null,
            name: 'Blue Variant Updated',
            sku: 'BLU-001',
            isDefault: true,
            isActive: true,
            purchasePrice: '8.000000',
            salesPrice: '16.000000',
            metadata: ['color' => 'blue', 'updated' => true],
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Blue Variant Updated', $updated->getName());
        $this->assertTrue($updated->isDefault());
        $this->assertSame('8.000000', $updated->getPurchasePrice());
        $this->assertSame('16.000000', $updated->getSalesPrice());
    }

    public function test_find_by_product_and_sku_returns_domain_entity(): void
    {
        $this->insertVariantRow(id: 8501, tenantId: 81, productId: 8001, name: 'Red Variant', sku: 'RED-001');
        $this->insertVariantRow(id: 8502, tenantId: 82, productId: 8002, name: 'Red Variant 2', sku: 'RED-001');

        /** @var ProductVariantRepositoryInterface $repository */
        $repository = app(ProductVariantRepositoryInterface::class);

        $found = $repository->findByProductAndSku(8001, 'RED-001', 81);

        $this->assertInstanceOf(ProductVariant::class, $found);
        $this->assertSame(8501, $found->getId());
        $this->assertSame(8001, $found->getProductId());
    }

    public function test_paginate_and_where_return_mapped_domain_entities(): void
    {
        $this->insertVariantRow(id: 8601, tenantId: 81, productId: 8001, name: 'Variant C', sku: 'C-001');
        $this->insertVariantRow(id: 8602, tenantId: 81, productId: 8001, name: 'Variant A', sku: 'A-001');
        $this->insertVariantRow(id: 8603, tenantId: 82, productId: 8002, name: 'Variant X', sku: 'X-001');

        /** @var ProductVariantRepositoryInterface $repository */
        $repository = app(ProductVariantRepositoryInterface::class);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 81)
            ->where('product_id', 8001)
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $items = $paginator->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(ProductVariant::class, $items[0]);
        $this->assertSame('Variant A', $items[0]->getName());
        $this->assertSame('Variant C', $items[1]->getName());

        $collection = $repository
            ->resetCriteria()
            ->where('tenant_id', 81)
            ->where('product_id', 8001)
            ->get();

        $this->assertCount(2, $collection);
        $this->assertContainsOnlyInstancesOf(ProductVariant::class, $collection);
    }

    private function insertVariantRow(int $id, int $tenantId, int $productId, string $name, string $sku): void
    {
        DB::table('product_variants')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'product_id' => $productId,
            'sku' => $sku,
            'name' => $name,
            'is_default' => false,
            'is_active' => true,
            'purchase_price' => '5.000000',
            'sales_price' => '9.000000',
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedReferenceData(): void
    {
        $this->insertTenantAndUomAndProduct(81, 8101, 8001, 'Product A', 'product-a', 'P-A-001');
        $this->insertTenantAndUomAndProduct(82, 8201, 8002, 'Product B', 'product-b', 'P-B-001');
    }

    private function insertTenantAndUomAndProduct(
        int $tenantId,
        int $uomId,
        int $productId,
        string $productName,
        string $productSlug,
        string $productSku
    ): void {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
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
            'deleted_at' => null,
        ]);

        DB::table('units_of_measure')->insert([
            'id' => $uomId,
            'tenant_id' => $tenantId,
            'name' => 'Each',
            'symbol' => 'EA'.$tenantId,
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => $productId,
            'tenant_id' => $tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => $productName,
            'slug' => $productSlug,
            'sku' => $productSku,
            'description' => null,
            'base_uom_id' => $uomId,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'purchase_price' => null,
            'sales_price' => null,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function ensureAccountsTableExists(): void
    {
        if (Schema::hasTable('accounts')) {
            return;
        }

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }
}
