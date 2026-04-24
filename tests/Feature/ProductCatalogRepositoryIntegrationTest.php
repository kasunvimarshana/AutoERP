<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\Entities\ProductAttribute;
use Modules\Product\Domain\Entities\ProductAttributeGroup;
use Modules\Product\Domain\Entities\ProductAttributeValue;
use Modules\Product\Domain\Entities\VariantAttribute;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeGroupRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeValueRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\VariantAttributeRepositoryInterface;
use Tests\TestCase;

class ProductCatalogRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAccountsTableExists();
        $this->seedBaseCatalogData();
    }

    public function test_attribute_group_repository_save_find_and_paginate(): void
    {
        /** @var ProductAttributeGroupRepositoryInterface $repository */
        $repository = app(ProductAttributeGroupRepositoryInterface::class);

        $created = $repository->save(new ProductAttributeGroup(
            tenantId: 41,
            name: 'Dimensions',
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('Dimensions', $created->getName());

        $updated = $repository->save(new ProductAttributeGroup(
            tenantId: 41,
            name: 'Physical Dimensions',
            id: $created->getId(),
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Physical Dimensions', $updated->getName());

        $found = $repository->find($created->getId());
        $this->assertInstanceOf(ProductAttributeGroup::class, $found);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 41)
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $this->assertGreaterThanOrEqual(1, count($paginator->items()));
        $this->assertContainsOnlyInstancesOf(ProductAttributeGroup::class, $paginator->items());
    }

    public function test_attribute_repository_save_find_and_paginate(): void
    {
        /** @var ProductAttributeRepositoryInterface $repository */
        $repository = app(ProductAttributeRepositoryInterface::class);

        $created = $repository->save(new ProductAttribute(
            tenantId: 41,
            name: 'Color',
            type: 'select',
            isRequired: true,
            groupId: 4101,
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('Color', $created->getName());
        $this->assertTrue($created->isRequired());

        $updated = $repository->save(new ProductAttribute(
            tenantId: 41,
            name: 'Product Color',
            type: 'select',
            isRequired: false,
            groupId: 4101,
            id: $created->getId(),
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Product Color', $updated->getName());
        $this->assertFalse($updated->isRequired());

        $found = $repository->find($created->getId());
        $this->assertInstanceOf(ProductAttribute::class, $found);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 41)
            ->where('type', 'select')
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $this->assertGreaterThanOrEqual(1, count($paginator->items()));
        $this->assertContainsOnlyInstancesOf(ProductAttribute::class, $paginator->items());
    }

    public function test_attribute_value_repository_save_find_and_paginate(): void
    {
        /** @var ProductAttributeValueRepositoryInterface $repository */
        $repository = app(ProductAttributeValueRepositoryInterface::class);

        $created = $repository->save(new ProductAttributeValue(
            tenantId: 41,
            attributeId: 4201,
            value: 'Blue',
            sortOrder: 1,
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('Blue', $created->getValue());

        $updated = $repository->save(new ProductAttributeValue(
            tenantId: 41,
            attributeId: 4201,
            value: 'Navy Blue',
            sortOrder: 2,
            id: $created->getId(),
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Navy Blue', $updated->getValue());
        $this->assertSame(2, $updated->getSortOrder());

        $found = $repository->find($created->getId());
        $this->assertInstanceOf(ProductAttributeValue::class, $found);

        $paginator = $repository
            ->resetCriteria()
            ->where('attribute_id', 4201)
            ->orderBy('sort_order', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $this->assertGreaterThanOrEqual(1, count($paginator->items()));
        $this->assertContainsOnlyInstancesOf(ProductAttributeValue::class, $paginator->items());
    }

    public function test_variant_attribute_repository_save_find_and_paginate(): void
    {
        /** @var VariantAttributeRepositoryInterface $repository */
        $repository = app(VariantAttributeRepositoryInterface::class);

        $created = $repository->save(new VariantAttribute(
            tenantId: 41,
            productId: 4301,
            attributeId: 4201,
            isRequired: true,
            isVariationAxis: true,
            displayOrder: 10,
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame(10, $created->getDisplayOrder());

        $updated = $repository->save(new VariantAttribute(
            tenantId: 41,
            productId: 4301,
            attributeId: 4201,
            isRequired: false,
            isVariationAxis: true,
            displayOrder: 20,
            id: $created->getId(),
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertFalse($updated->isRequired());
        $this->assertSame(20, $updated->getDisplayOrder());

        $found = $repository->find($created->getId());
        $this->assertInstanceOf(VariantAttribute::class, $found);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 41)
            ->where('product_id', 4301)
            ->orderBy('display_order', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $this->assertGreaterThanOrEqual(1, count($paginator->items()));
        $this->assertContainsOnlyInstancesOf(VariantAttribute::class, $paginator->items());
    }

    public function test_combo_item_repository_save_find_and_paginate(): void
    {
        /** @var ComboItemRepositoryInterface $repository */
        $repository = app(ComboItemRepositoryInterface::class);

        $created = $repository->save(new ComboItem(
            tenantId: 41,
            comboProductId: 4302,
            componentProductId: 4301,
            componentVariantId: 4401,
            quantity: '2.500000',
            uomId: 401,
            metadata: ['source' => 'test'],
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame(4302, $created->getComboProductId());
        $this->assertSame(4301, $created->getComponentProductId());

        $updated = $repository->save(new ComboItem(
            tenantId: 41,
            comboProductId: 4302,
            componentProductId: 4301,
            componentVariantId: null,
            quantity: '3.000000',
            uomId: 401,
            metadata: ['source' => 'updated'],
            id: $created->getId(),
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('3.000000', $updated->getQuantity());
        $this->assertNull($updated->getComponentVariantId());

        $found = $repository->find($created->getId());
        $this->assertInstanceOf(ComboItem::class, $found);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 41)
            ->where('combo_product_id', 4302)
            ->paginate(15, ['*'], 'page', 1);

        $this->assertGreaterThanOrEqual(1, count($paginator->items()));
        $this->assertContainsOnlyInstancesOf(ComboItem::class, $paginator->items());
    }

    private function seedBaseCatalogData(): void
    {
        DB::table('tenants')->insert([
            'id' => 41,
            'name' => 'Tenant 41',
            'slug' => 'tenant-41',
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
            'id' => 401,
            'tenant_id' => 41,
            'name' => 'Each',
            'symbol' => 'EA41',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attribute_groups')->insert([
            'id' => 4101,
            'tenant_id' => 41,
            'name' => 'Visual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attributes')->insert([
            'id' => 4201,
            'tenant_id' => 41,
            'group_id' => 4101,
            'name' => 'Color',
            'type' => 'select',
            'is_required' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => 4301,
            'tenant_id' => 41,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Component Product',
            'slug' => 'component-product',
            'sku' => 'COMP-4301',
            'description' => null,
            'image_path' => null,
            'base_uom_id' => 401,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
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

        DB::table('products')->insert([
            'id' => 4302,
            'tenant_id' => 41,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'combo',
            'name' => 'Combo Product',
            'slug' => 'combo-product',
            'sku' => 'COMBO-4302',
            'description' => null,
            'image_path' => null,
            'base_uom_id' => 401,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
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

        DB::table('product_variants')->insert([
            'id' => 4401,
            'tenant_id' => 41,
            'product_id' => 4301,
            'sku' => 'COMP-4301-BLUE',
            'name' => 'Component Blue',
            'is_default' => true,
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
