<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Repositories\InventoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InventoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private InventoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(InventoryRepository::class);
    }

    /** @test */
    public function it_reserves_stock_when_available(): void
    {
        $product = Product::factory()->create(['tenant_id' => 'tenant-1']);
        InventoryItem::factory()->create([
            'product_id'         => $product->id,
            'tenant_id'          => 'tenant-1',
            'quantity_available' => 10,
            'quantity_reserved'  => 0,
        ]);

        $result = $this->repository->reserveStock($product->id, 'tenant-1', 5);

        $this->assertTrue($result);
        $this->assertDatabaseHas('inventory_items', [
            'product_id'         => $product->id,
            'quantity_available' => 5,
            'quantity_reserved'  => 5,
        ]);
    }

    /** @test */
    public function it_returns_false_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create(['tenant_id' => 'tenant-1']);
        InventoryItem::factory()->create([
            'product_id'         => $product->id,
            'tenant_id'          => 'tenant-1',
            'quantity_available' => 3,
            'quantity_reserved'  => 0,
        ]);

        $result = $this->repository->reserveStock($product->id, 'tenant-1', 10);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_releases_stock_as_compensation(): void
    {
        $product = Product::factory()->create(['tenant_id' => 'tenant-1']);
        InventoryItem::factory()->create([
            'product_id'         => $product->id,
            'tenant_id'          => 'tenant-1',
            'quantity_available' => 5,
            'quantity_reserved'  => 5,
        ]);

        $result = $this->repository->releaseStock($product->id, 'tenant-1', 5);

        $this->assertTrue($result);
        $this->assertDatabaseHas('inventory_items', [
            'product_id'         => $product->id,
            'quantity_available' => 10,
            'quantity_reserved'  => 0,
        ]);
    }
}
