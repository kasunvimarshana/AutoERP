<?php

namespace Tests\Unit;

use App\DTOs\ProductDTO;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\QueryBuilder\QueryBuilder;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository();
    }

    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'tenant_id' => $this->tenantId,
            'sku'       => 'REPO-'.uniqid(),
            'name'      => 'Repo Test Product',
            'price'     => 50.00,
            'status'    => 'active',
            'is_active' => true,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // queryBuilder()
    // -------------------------------------------------------------------------

    public function test_query_builder_returns_query_builder_instance(): void
    {
        $this->assertInstanceOf(QueryBuilder::class, $this->repository->queryBuilder());
    }

    // -------------------------------------------------------------------------
    // findById()
    // -------------------------------------------------------------------------

    public function test_find_by_id_returns_correct_product(): void
    {
        $product = $this->makeProduct();

        $found = $this->repository->findById($product->id);

        $this->assertNotNull($found);
        $this->assertEquals($product->id, $found->id);
        $this->assertEquals($product->sku, $found->sku);
    }

    public function test_find_by_id_returns_null_for_nonexistent(): void
    {
        $this->assertNull($this->repository->findById(99999));
    }

    // -------------------------------------------------------------------------
    // findBySku()
    // -------------------------------------------------------------------------

    public function test_find_by_sku_returns_product_for_correct_tenant(): void
    {
        $product = $this->makeProduct(['sku' => 'FIND-BY-SKU']);

        $found = $this->repository->findBySku('FIND-BY-SKU', $this->tenantId);

        $this->assertNotNull($found);
        $this->assertEquals($product->id, $found->id);
    }

    public function test_find_by_sku_returns_null_for_different_tenant(): void
    {
        $this->makeProduct(['sku' => 'CROSS-TENANT-SKU']);

        $found = $this->repository->findBySku('CROSS-TENANT-SKU', 'other-tenant-xyz');

        $this->assertNull($found);
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    public function test_create_persists_product(): void
    {
        $dto = ProductDTO::fromRequest([
            'sku'       => 'CREATE-001',
            'name'      => 'Created Product',
            'price'     => 75.00,
            'is_active' => true,
            'status'    => 'active',
        ], $this->tenantId);

        $product = $this->repository->create($dto);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', ['sku' => 'CREATE-001', 'tenant_id' => $this->tenantId]);
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_saves_changes(): void
    {
        $product = $this->makeProduct(['name' => 'Before Update', 'sku' => 'UPD-REPO-001']);

        $dto = ProductDTO::fromRequest([
            'sku'       => 'UPD-REPO-001',
            'name'      => 'After Update',
            'price'     => 55.00,
            'is_active' => true,
            'status'    => 'active',
        ], $this->tenantId);

        $updated = $this->repository->update($product, $dto);

        $this->assertEquals('After Update', $updated->name);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'After Update']);
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function test_delete_soft_deletes_product(): void
    {
        $product = $this->makeProduct(['sku' => 'DEL-REPO-001']);

        $result = $this->repository->delete($product);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    // -------------------------------------------------------------------------
    // Tenant isolation
    // -------------------------------------------------------------------------

    public function test_paginate_only_returns_current_tenant_products(): void
    {
        $this->makeProduct(['sku' => 'MY-001']);

        Product::withoutGlobalScope('tenant')->create([
            'tenant_id' => 'foreign-tenant',
            'sku'       => 'FOREIGN-001',
            'name'      => 'Foreign Product',
            'price'     => 1,
            'status'    => 'active',
            'is_active' => true,
        ]);

        $paginator = $this->repository->paginate(15);

        $skus = $paginator->pluck('sku')->toArray();
        $this->assertContains('MY-001', $skus);
        $this->assertNotContains('FOREIGN-001', $skus);
    }
}
