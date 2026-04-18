<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Product\Domain\Entities\ProductBrand;
use Modules\Product\Domain\RepositoryInterfaces\ProductBrandRepositoryInterface;
use Tests\TestCase;

class ProductBrandRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenants();
    }

    public function test_save_creates_and_updates_product_brand(): void
    {
        /** @var ProductBrandRepositoryInterface $repository */
        $repository = app(ProductBrandRepositoryInterface::class);

        $created = $repository->save(new ProductBrand(
            tenantId: 11,
            name: 'Acme',
            slug: 'acme',
            code: 'ACM',
            isActive: true,
        ));

        $this->assertNotNull($created->getId());
        $this->assertSame('Acme', $created->getName());

        $updated = $repository->save(new ProductBrand(
            id: $created->getId(),
            tenantId: 11,
            name: 'Acme Prime',
            slug: 'acme-prime',
            code: 'ACM',
            isActive: true,
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Acme Prime', $updated->getName());
        $this->assertSame('acme-prime', $updated->getSlug());
    }

    public function test_find_by_tenant_and_code_returns_domain_entity(): void
    {
        $this->insertProductBrandRow(id: 801, tenantId: 21, name: 'Acme', slug: 'acme', code: 'ACM');
        $this->insertProductBrandRow(id: 802, tenantId: 22, name: 'Acme Other', slug: 'acme-other', code: 'ACM');

        /** @var ProductBrandRepositoryInterface $repository */
        $repository = app(ProductBrandRepositoryInterface::class);

        $found = $repository->findByTenantAndCode(21, 'ACM');

        $this->assertInstanceOf(ProductBrand::class, $found);
        $this->assertSame(801, $found->getId());
        $this->assertSame(21, $found->getTenantId());
    }

    public function test_paginate_and_where_return_mapped_domain_entities(): void
    {
        $this->insertProductBrandRow(id: 901, tenantId: 31, name: 'C Brand', slug: 'c-brand', code: 'C');
        $this->insertProductBrandRow(id: 902, tenantId: 31, name: 'B Brand', slug: 'b-brand', code: 'B');
        $this->insertProductBrandRow(id: 903, tenantId: 32, name: 'X Brand', slug: 'x-brand', code: 'X');

        /** @var ProductBrandRepositoryInterface $repository */
        $repository = app(ProductBrandRepositoryInterface::class);

        $paginator = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->orderBy('name', 'asc')
            ->paginate(15, ['*'], 'page', 1);

        $items = $paginator->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(ProductBrand::class, $items[0]);
        $this->assertSame('B Brand', $items[0]->getName());
        $this->assertSame('C Brand', $items[1]->getName());

        $collection = $repository
            ->resetCriteria()
            ->where('tenant_id', 31)
            ->get();

        $this->assertCount(2, $collection);
        $this->assertContainsOnlyInstancesOf(ProductBrand::class, $collection);
    }

    private function insertProductBrandRow(int $id, int $tenantId, string $name, string $slug, string $code): void
    {
        DB::table('product_brands')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'parent_id' => null,
            'name' => $name,
            'slug' => $slug,
            'code' => $code,
            'path' => null,
            'depth' => 0,
            'is_active' => true,
            'website' => null,
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
