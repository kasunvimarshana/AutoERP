<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Tests\TestCase;

class ProductCategoryRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenants();
    }

    public function test_save_creates_and_updates_product_category(): void
    {
        /** @var ProductCategoryRepositoryInterface $repository */
        $repository = app(ProductCategoryRepositoryInterface::class);

        $created = $repository->save(new ProductCategory(
            tenantId: 11,
            name: 'Electronics',
            slug: 'electronics',
            code: 'ELC',
            isActive: true,
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('Electronics', $created->getName());

        $updated = $repository->save(new ProductCategory(
            id: $created->getId(),
            tenantId: 11,
            name: 'Electronics & Gadgets',
            slug: 'electronics-gadgets',
            code: 'ELC',
            isActive: true,
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Electronics & Gadgets', $updated->getName());
        $this->assertSame('electronics-gadgets', $updated->getSlug());
    }

    public function test_find_by_tenant_and_code_returns_domain_entity(): void
    {
        $this->insertProductCategoryRow(id: 811, tenantId: 21, name: 'Electronics', slug: 'electronics', code: 'ELC');
        $this->insertProductCategoryRow(id: 812, tenantId: 22, name: 'Electronics Other', slug: 'electronics-other', code: 'ELC');

        /** @var ProductCategoryRepositoryInterface $repository */
        $repository = app(ProductCategoryRepositoryInterface::class);

        $found = $repository->findByTenantAndCode(21, 'ELC');

        $this->assertInstanceOf(ProductCategory::class, $found);
        $this->assertSame(811, $found->getId());
        $this->assertSame(21, $found->getTenantId());
    }

    public function test_paginate_and_where_return_mapped_domain_entities(): void
    {
        $this->insertProductCategoryRow(id: 911, tenantId: 31, name: 'C Category', slug: 'c-category', code: 'C');
        $this->insertProductCategoryRow(id: 912, tenantId: 31, name: 'B Category', slug: 'b-category', code: 'B');
        $this->insertProductCategoryRow(id: 913, tenantId: 32, name: 'X Category', slug: 'x-category', code: 'X');

        /** @var ProductCategoryRepositoryInterface $repository */
        $repository = app(ProductCategoryRepositoryInterface::class);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $items = $paginator->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(ProductCategory::class, $items[0]);
        $this->assertSame('B Category', $items[0]->getName());
        $this->assertSame('C Category', $items[1]->getName());

        $collection = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->get();

        $this->assertCount(2, $collection);
        $this->assertContainsOnlyInstancesOf(ProductCategory::class, $collection);
    }

    private function insertProductCategoryRow(int $id, int $tenantId, string $name, string $slug, string $code): void
    {
        DB::table('product_categories')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'name' => $name,
            'slug' => $slug,
            'code' => $code,
            'path' => null,
            'depth' => 0,
            'is_active' => true,
            'description' => null,
            'attributes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTenants(): void
    {
        foreach ([11, 21, 22, 31, 32] as $tenantId) {
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
        }
    }
}
