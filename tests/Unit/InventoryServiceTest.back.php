<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Product\Models\Product;
use App\Modules\Warehouse\Models\Warehouse;

class InventoryServiceTest extends TestCase
{
    public function test_inbound_increases_balance()
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $service = app(InventoryService::class);
        $service->inbound($product, 10, $warehouse->id, null, 5.00, 'purchase', null);

        $this->assertDatabaseHas('inventory_balances', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity_on_hand' => 10,
        ]);
    }
}