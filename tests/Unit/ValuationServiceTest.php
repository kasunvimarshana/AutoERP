<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Product\Models\Product;
use App\Modules\Warehouse\Models\Warehouse;

class ValuationServiceTest extends TestCase
{

public function test_fifo_consumption()
{
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    // receive 10 @ $10, then 10 @ $12
    $this->inventoryService->receiveStock($product->id, $warehouse->id, 10, 10);
    $this->inventoryService->receiveStock($product->id, $warehouse->id, 10, 12);

    // consume 15 units
    $cost = $this->valuationService->setContext($product->id, $warehouse->id, 'fifo')
        ->consume(15, 'test', 1);

    $this->assertEquals(10*10 + 5*12, $cost); // 160
}

}