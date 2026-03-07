<?php

namespace Tests\Unit;

use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\Events\ProductCreated;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Repositories\Interfaces\ProductRepositoryInterface;
use App\Modules\Product\Services\ProductService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductRepositoryInterface $repository;
    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ProductRepositoryInterface::class);
        $this->service = new ProductService($this->repository);
    }

    public function test_get_product_throws_exception_when_not_found(): void
    {
        $this->repository->shouldReceive('findById')->with(999)->andReturn(null);

        $this->expectException(ModelNotFoundException::class);

        $this->service->getProduct(999);
    }

    public function test_get_product_returns_product_when_found(): void
    {
        $product = new Product(['id' => 1, 'name' => 'Test Product', 'sku' => 'TEST-001']);
        $this->repository->shouldReceive('findById')->with(1)->andReturn($product);

        $result = $this->service->getProduct(1);

        $this->assertSame($product, $result);
    }

    public function test_create_product_throws_exception_when_sku_exists(): void
    {
        $dto = ProductDTO::fromArray([
            'name' => 'Test',
            'sku' => 'EXISTING-SKU',
            'price' => 10.00,
        ]);

        $existing = new Product(['sku' => 'EXISTING-SKU']);
        $this->repository->shouldReceive('findBySku')->with('EXISTING-SKU')->andReturn($existing);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $this->expectException(\InvalidArgumentException::class);

        $this->service->createProduct($dto);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
