<?php

namespace Tests\Unit;

use App\Enums\TenantStatus;
use App\Models\Product;
use App\Models\Tenant;
use App\Repository\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository $repository;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ProductRepository;
        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
    }

    public function test_can_create_product_via_repository(): void
    {
        $product = $this->repository->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'goods',
            'name' => 'Repository Widget',
            'slug' => 'repository-widget',
            'sku' => 'RW-001',
            'base_price' => '19.99',
            'currency' => 'USD',
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Repository Widget', $product->name);
        $this->assertDatabaseHas('products', ['sku' => 'RW-001']);
    }

    public function test_can_find_product_by_id(): void
    {
        $created = $this->repository->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'goods',
            'name' => 'Find Me',
            'slug' => 'find-me',
            'sku' => 'FM-001',
            'base_price' => '9.99',
            'currency' => 'USD',
        ]);

        $found = $this->repository->findById($created->id);

        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
    }

    public function test_find_by_id_returns_null_for_nonexistent(): void
    {
        $found = $this->repository->findById('00000000-0000-0000-0000-000000000000');

        $this->assertNull($found);
    }

    public function test_can_update_product_via_repository(): void
    {
        $product = $this->repository->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'goods',
            'name' => 'Original',
            'slug' => 'original',
            'sku' => 'ORIG-001',
            'base_price' => '5.00',
            'currency' => 'USD',
        ]);

        $updated = $this->repository->update($product->id, ['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Name']);
    }

    public function test_can_delete_product_via_repository(): void
    {
        $product = $this->repository->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'goods',
            'name' => 'To Delete',
            'slug' => 'to-delete',
            'sku' => 'DEL-001',
            'base_price' => '1.00',
            'currency' => 'USD',
        ]);

        $result = $this->repository->delete($product->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_can_find_all_products_for_tenant(): void
    {
        $this->repository->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'goods',
            'name' => 'Product A',
            'slug' => 'product-a',
            'sku' => 'PA-001',
            'base_price' => '10.00',
            'currency' => 'USD',
        ]);

        $this->repository->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'service',
            'name' => 'Product B',
            'slug' => 'product-b',
            'sku' => 'PB-001',
            'base_price' => '20.00',
            'currency' => 'USD',
        ]);

        $results = $this->repository->findAll(['tenant_id' => $this->tenant->id]);

        $this->assertCount(2, $results);
    }

    public function test_find_by_sku_returns_correct_product(): void
    {
        $this->repository->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'goods',
            'name' => 'SKU Product',
            'slug' => 'sku-product',
            'sku' => 'UNIQUE-SKU-123',
            'base_price' => '15.00',
            'currency' => 'USD',
        ]);

        $found = $this->repository->findBySku($this->tenant->id, 'UNIQUE-SKU-123');

        $this->assertNotNull($found);
        $this->assertEquals('UNIQUE-SKU-123', $found->sku);
    }
}
