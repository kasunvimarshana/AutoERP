<?php

namespace Tests\Unit\Repositories;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository(new Product());
    }

    public function test_can_create_product(): void
    {
        $data = [
            'tenant_id' => 1, // Required field for tenant-aware models
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 99.99,
            'status' => 'active',
        ];

        $product = $this->repository->create($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(1, $product->tenant_id);
    }
}
