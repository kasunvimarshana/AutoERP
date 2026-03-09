<?php
namespace Tests\Feature\Stock;
use Tests\TestCase;
use App\Domain\Models\Product;
use App\Domain\Models\Warehouse;
use App\Domain\Models\StockLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockOperationsTest extends TestCase
{
    use RefreshDatabase;

    private string $tenantId = 'tenant_test';
    private array  $headers;
    private Product   $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->headers = ['X-Tenant-ID' => $this->tenantId, 'Authorization' => 'Bearer test_token', 'Accept' => 'application/json'];
        $this->product   = Product::factory()->create(['tenant_id' => $this->tenantId]);
        $this->warehouse = Warehouse::factory()->create(['tenant_id' => $this->tenantId]);
    }

    public function test_can_adjust_stock_receipt(): void
    {
        $response = $this->postJson('/api/v1/stock/adjust', [
            'product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id,
            'quantity' => 100, 'type' => 'receipt',
        ], $this->headers);
        $response->assertStatus(200);
        $this->assertDatabaseHas('stock_levels', ['product_id' => $this->product->id, 'quantity_on_hand' => 100]);
    }

    public function test_can_reserve_stock(): void
    {
        StockLevel::factory()->create(['tenant_id' => $this->tenantId, 'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id, 'quantity_available' => 50, 'quantity_on_hand' => 50]);
        $response = $this->postJson('/api/v1/stock/reserve', [
            'product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id,
            'quantity' => 10, 'reference_id' => 'ORDER-001', 'reference_type' => 'order',
        ], $this->headers);
        $response->assertStatus(201);
        $this->assertDatabaseHas('stock_levels', ['product_id' => $this->product->id, 'quantity_available' => 40, 'quantity_reserved' => 10]);
    }

    public function test_cannot_reserve_more_than_available(): void
    {
        StockLevel::factory()->create(['tenant_id' => $this->tenantId, 'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id, 'quantity_available' => 5, 'quantity_on_hand' => 5]);
        $response = $this->postJson('/api/v1/stock/reserve', [
            'product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id,
            'quantity' => 10, 'reference_id' => 'ORDER-002', 'reference_type' => 'order',
        ], $this->headers);
        $response->assertStatus(409);
    }

    public function test_can_transfer_stock(): void
    {
        $wh2 = Warehouse::factory()->create(['tenant_id' => $this->tenantId]);
        StockLevel::factory()->create(['tenant_id' => $this->tenantId, 'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id, 'quantity_available' => 50, 'quantity_on_hand' => 50]);
        $response = $this->postJson('/api/v1/stock/transfer', [
            'product_id' => $this->product->id, 'from_warehouse_id' => $this->warehouse->id,
            'to_warehouse_id' => $wh2->id, 'quantity' => 20,
        ], $this->headers);
        $response->assertStatus(200);
        $this->assertDatabaseHas('stock_levels', ['warehouse_id' => $this->warehouse->id, 'quantity_available' => 30]);
        $this->assertDatabaseHas('stock_levels', ['warehouse_id' => $wh2->id, 'quantity_available' => 20]);
    }
}
