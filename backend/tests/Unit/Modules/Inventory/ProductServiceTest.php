<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Services\TenantContext;
use Modules\Inventory\DTOs\ProductDTO;
use Modules\Inventory\Events\ProductCreated;
use Modules\Inventory\Events\ProductUpdated;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Repositories\ProductRepository;
use Modules\Inventory\Services\ProductService;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Mockery;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $service;
    private ProductRepository $repository;
    private TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->tenantContext = Mockery::mock(TenantContext::class);
        $this->tenantContext->shouldReceive('getTenantId')->andReturn(1);
        
        $this->repository = Mockery::mock(ProductRepository::class);
        $this->service = new ProductService($this->tenantContext, $this->repository);

        Event::fake([ProductCreated::class, ProductUpdated::class]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_product_successfully(): void
    {
        // Arrange
        $productData = [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'type' => 'inventory',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'status' => 'active',
        ];

        $dto = ProductDTO::fromArray($productData);
        $expectedProduct = new Product($productData);
        $expectedProduct->id = 1;

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['name'] === 'Test Product' && $data['sku'] === 'TEST-001';
            }))
            ->andReturn($expectedProduct);

        // Act
        $result = $this->service->create($dto);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('Test Product', $result->name);
        $this->assertEquals('TEST-001', $result->sku);
        Event::assertDispatched(ProductCreated::class);
    }

    /** @test */
    public function it_generates_sku_automatically_when_not_provided(): void
    {
        // Arrange
        $productData = [
            'name' => 'Auto SKU Product',
            'type' => 'inventory',
            'cost_price' => 50.00,
            'selling_price' => 75.00,
        ];

        $dto = ProductDTO::fromArray($productData);
        
        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['sku']) && !empty($data['sku']);
            }))
            ->andReturn(new Product(['sku' => 'PROD-12345']));

        // Act
        $result = $this->service->create($dto);

        // Assert
        $this->assertNotEmpty($result->sku);
    }

    /** @test */
    public function it_can_update_product_successfully(): void
    {
        // Arrange
        $productId = 1;
        $updateData = [
            'name' => 'Updated Product',
            'selling_price' => 200.00,
        ];

        $dto = ProductDTO::fromArray($updateData);
        $existingProduct = new Product([
            'id' => $productId,
            'name' => 'Original Product',
            'sku' => 'TEST-001',
            'selling_price' => 150.00,
        ]);
        
        $updatedProduct = new Product([
            'id' => $productId,
            'name' => 'Updated Product',
            'sku' => 'TEST-001',
            'selling_price' => 200.00,
        ]);

        $this->repository->shouldReceive('find')->with($productId)->andReturn($existingProduct);
        $this->repository->shouldReceive('update')->once()->andReturn($updatedProduct);

        // Act
        $result = $this->service->update($productId, $dto);

        // Assert
        $this->assertEquals('Updated Product', $result->name);
        $this->assertEquals(200.00, $result->selling_price);
        Event::assertDispatched(ProductUpdated::class);
    }

    /** @test */
    public function it_can_delete_product_successfully(): void
    {
        // Arrange
        $productId = 1;
        $product = new Product(['id' => $productId, 'name' => 'Test Product']);

        $this->repository->shouldReceive('find')->with($productId)->andReturn($product);
        $this->repository->shouldReceive('delete')->with($productId)->once()->andReturn(true);

        // Act
        $result = $this->service->delete($productId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_find_product_by_sku(): void
    {
        // Arrange
        $sku = 'TEST-001';
        $expectedProduct = new Product(['sku' => $sku, 'name' => 'Test Product']);

        $this->repository
            ->shouldReceive('findBySKU')
            ->with($sku)
            ->once()
            ->andReturn($expectedProduct);

        // Act
        $result = $this->service->findBySKU($sku);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals($sku, $result->sku);
    }

    /** @test */
    public function it_can_check_if_product_has_stock(): void
    {
        // Arrange
        $productId = 1;
        $warehouseId = 1;

        $this->repository
            ->shouldReceive('hasStock')
            ->with($productId, $warehouseId)
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->service->hasStock($productId, $warehouseId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_low_stock_products(): void
    {
        // Arrange
        $threshold = 10;
        $lowStockProducts = collect([
            new Product(['name' => 'Product 1', 'reorder_point' => 5]),
            new Product(['name' => 'Product 2', 'reorder_point' => 8]),
        ]);

        $this->repository
            ->shouldReceive('getLowStockProducts')
            ->with($threshold)
            ->once()
            ->andReturn($lowStockProducts);

        // Act
        $result = $this->service->getLowStockProducts($threshold);

        // Assert
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_validates_profit_margin_calculation(): void
    {
        // Arrange
        $productData = [
            'name' => 'Margin Test Product',
            'sku' => 'MARGIN-001',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
        ];

        $product = new Product($productData);

        // Act
        $profitMargin = $product->profit_margin;

        // Assert
        // Profit margin = ((selling_price - cost_price) / selling_price) * 100
        // = ((150 - 100) / 150) * 100 = 33.33%
        $this->assertEquals(33.33, round($profitMargin, 2));
    }

    /** @test */
    public function it_validates_markup_calculation(): void
    {
        // Arrange
        $productData = [
            'name' => 'Markup Test Product',
            'sku' => 'MARKUP-001',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
        ];

        $product = new Product($productData);

        // Act
        $markup = $product->markup;

        // Assert
        // Markup = ((selling_price - cost_price) / cost_price) * 100
        // = ((150 - 100) / 100) * 100 = 50%
        $this->assertEquals(50.00, round($markup, 2));
    }
}
