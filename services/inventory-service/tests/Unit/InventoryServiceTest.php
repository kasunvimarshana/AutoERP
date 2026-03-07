<?php

namespace Tests\Unit;

use App\Modules\Inventory\DTOs\StockAdjustmentDTO;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Repositories\Interfaces\InventoryRepositoryInterface;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    private InventoryRepositoryInterface $repository;
    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(InventoryRepositoryInterface::class);
        $this->service = new InventoryService($this->repository);
    }

    public function test_get_inventory_item_throws_when_not_found(): void
    {
        $this->repository->shouldReceive('findById')->with(999)->andReturn(null);
        $this->expectException(ModelNotFoundException::class);
        $this->service->getInventoryItem(999);
    }

    public function test_get_inventory_item_returns_item_when_found(): void
    {
        $item = new InventoryItem(['id' => 1, 'product_id' => 1, 'quantity' => 50]);
        $this->repository->shouldReceive('findById')->with(1)->andReturn($item);
        $result = $this->service->getInventoryItem(1);
        $this->assertSame($item, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
