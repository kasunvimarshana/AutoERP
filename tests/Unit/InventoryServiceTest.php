<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Product\Models\Product;
use App\Modules\Warehouse\Models\Warehouse;

class InventoryServiceTest extends TestCase
{
    protected $inventoryService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);
    }
    
    /** @test */
    public function it_can_process_inbound_transaction()
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        
        $transaction = $this->inventoryService->inbound([
            'transaction_type' => 'purchase',
            'product_id' => $product->id,
            'to_warehouse_id' => $warehouse->id,
            'quantity' => 100,
            'uom_id' => $product->default_uom_id,
            'unit_cost' => 10.50,
            'reference_type' => 'purchase_order',
            'reference_id' => 'PO-123',
        ]);
        
        $this->assertNotNull($transaction);
        $this->assertEquals(100, $transaction->quantity);
        
        $balance = $product->inventoryBalances()
            ->where('warehouse_id', $warehouse->id)
            ->first();
            
        $this->assertEquals(100, $balance->quantity_on_hand);
    }
    
    /** @test */
    public function it_calculates_fifo_cost_correctly()
    {
        $product = Product::factory()->create(['valuation_method' => 'fifo']);
        $warehouse = Warehouse::factory()->create();
        
        // First inbound at $10
        $this->inventoryService->inbound([
            'product_id' => $product->id,
            'to_warehouse_id' => $warehouse->id,
            'quantity' => 50,
            'unit_cost' => 10,
            // ... other required fields
        ]);
        
        // Second inbound at $12
        $this->inventoryService->inbound([
            'product_id' => $product->id,
            'to_warehouse_id' => $warehouse->id,
            'quantity' => 50,
            'unit_cost' => 12,
            // ... other required fields
        ]);
        
        // Outbound 75 units should use FIFO: 50@$10 + 25@$12 = $800
        $transaction = $this->inventoryService->outbound([
            'product_id' => $product->id,
            'from_warehouse_id' => $warehouse->id,
            'quantity' => 75,
            // ... other required fields
        ]);
        
        $this->assertEquals(800, $transaction->total_cost);
        $this->assertEquals(10.67, round($transaction->unit_cost, 2));
    }
}