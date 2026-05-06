<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Tests\TestCase;

class ProductRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAccountsTableExists();
        $this->seedReferenceData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_save_creates_and_updates_product(): void
    {
        /** @var ProductRepositoryInterface $repository */
        $repository = app(ProductRepositoryInterface::class);

        $created = $repository->save(new Product(
            tenantId: 11,
            type: 'physical',
            name: 'Widget A',
            slug: 'widget-a',
            sku: 'W-A-001',
            baseUomId: 101,
            isActive: true,
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('Widget A', $created->getName());

        $updated = $repository->save(new Product(
            id: $created->getId(),
            tenantId: 11,
            type: 'physical',
            name: 'Widget A Prime',
            slug: 'widget-a-prime',
            sku: 'W-A-001',
            baseUomId: 101,
            isActive: true,
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Widget A Prime', $updated->getName());
        $this->assertSame('widget-a-prime', $updated->getSlug());
    }

    public function test_find_by_tenant_and_sku_returns_domain_entity(): void
    {
        $this->insertProductRow(id: 501, tenantId: 21, baseUomId: 102, name: 'Searchable', slug: 'searchable', sku: 'SKU-501');
        $this->insertProductRow(id: 502, tenantId: 22, baseUomId: 103, name: 'Other Tenant', slug: 'other-tenant', sku: 'SKU-501');

        /** @var ProductRepositoryInterface $repository */
        $repository = app(ProductRepositoryInterface::class);

        $found = $repository->findByTenantAndSku(21, 'SKU-501');

        $this->assertInstanceOf(Product::class, $found);
        $this->assertSame(501, $found->getId());
        $this->assertSame(21, $found->getTenantId());
        $this->assertSame('Searchable', $found->getName());
    }

    public function test_paginate_and_where_return_mapped_domain_entities(): void
    {
        $this->insertProductRow(id: 601, tenantId: 31, baseUomId: 104, name: 'Widget C', slug: 'widget-c', sku: 'W-C-001');
        $this->insertProductRow(id: 602, tenantId: 31, baseUomId: 104, name: 'Widget B', slug: 'widget-b', sku: 'W-B-001');
        $this->insertProductRow(id: 603, tenantId: 32, baseUomId: 105, name: 'Widget X', slug: 'widget-x', sku: 'W-X-001');

        /** @var ProductRepositoryInterface $repository */
        $repository = app(ProductRepositoryInterface::class);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $items = $paginator->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(Product::class, $items[0]);
        $this->assertSame('Widget B', $items[0]->getName());
        $this->assertSame('Widget C', $items[1]->getName());

        $collection = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->get();

        $this->assertCount(2, $collection);
        $this->assertContainsOnlyInstancesOf(Product::class, $collection);
    }

    private function insertProductRow(int $id, int $tenantId, int $baseUomId, string $name, string $slug, string $sku): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'description' => null,
            'base_uom_id' => $baseUomId,
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
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function seedReferenceData(): void
    {
        $this->insertTenantAndUom(11, 101);
        $this->insertTenantAndUom(21, 102);
        $this->insertTenantAndUom(22, 103);
        $this->insertTenantAndUom(31, 104);
        $this->insertTenantAndUom(32, 105);
    }

    private function insertTenantAndUom(int $tenantId, int $uomId): void
    {
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
