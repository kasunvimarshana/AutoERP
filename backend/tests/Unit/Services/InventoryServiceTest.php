<?php

namespace Tests\Unit\Services;

use App\Enums\StockMovementType;
use App\Models\Tenant;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Repositories\ProductRepository;
use App\Modules\Inventory\Repositories\StockLedgerRepository;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\UnitTestCase;

/**
 * Inventory Service Test
 *
 * Tests the business logic in the InventoryService.
 */
class InventoryServiceTest extends UnitTestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    private ProductRepository $productRepository;

    private StockLedgerRepository $stockLedgerRepository;

    private Tenant $tenant;

    /**
     * Setup the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->productRepository = app(ProductRepository::class);
        $this->stockLedgerRepository = app(StockLedgerRepository::class);
        $this->service = new InventoryService(
            $this->productRepository,
            $this->stockLedgerRepository
        );
    }

    /** @test */
    public function it_can_record_a_stock_purchase_movement()
    {
        $product = Product::factory()
            ->forTenant($this->tenant)
            ->inventory()
            ->create();

        $dto = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 100,
            unitCost: 50.00,
            warehouseId: null,
            locationId: null,
            batchNumber: null,
            lotNumber: null,
            serialNumber: null,
            manufacturingDate: null,
            expiryDate: null,
            referenceType: null,
            referenceId: null,
            notes: 'Initial purchase',
            metadata: [],
            transactionDate: now()
        );

        $ledger = $this->service->recordStockMovement($dto);

        $this->assertNotNull($ledger);
        $this->assertEquals($product->id, $ledger->product_id);
        $this->assertEquals(StockMovementType::PURCHASE, $ledger->movement_type);
        $this->assertEquals(100, $ledger->quantity);
        $this->assertEquals(50.00, $ledger->unit_cost);
        $this->assertEquals(5000.00, $ledger->total_cost);
        $this->assertTrue($ledger->isIncrease());
    }

    /** @test */
    public function it_can_record_a_stock_sale_movement()
    {
        $product = Product::factory()
            ->forTenant($this->tenant)
            ->inventory()
            ->create();

        // First add some stock
        $purchaseDto = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 100,
            unitCost: 50.00
        );
        $this->service->recordStockMovement($purchaseDto);

        // Now record a sale
        $saleDto = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::SALE,
            quantity: 20,
            unitCost: 75.00
        );

        $ledger = $this->service->recordStockMovement($saleDto);

        $this->assertNotNull($ledger);
        $this->assertEquals(StockMovementType::SALE, $ledger->movement_type);
        $this->assertEquals(-20, $ledger->quantity);
        $this->assertTrue($ledger->isDecrease());
    }

    /** @test */
    public function it_calculates_correct_running_balance()
    {
        $product = Product::factory()
            ->forTenant($this->tenant)
            ->inventory()
            ->create();

        // Purchase 100 units
        $dto1 = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 100,
            unitCost: 50.00
        );
        $ledger1 = $this->service->recordStockMovement($dto1);
        $this->assertEquals(100, $ledger1->running_balance);

        // Sell 30 units
        $dto2 = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::SALE,
            quantity: 30,
            unitCost: 75.00
        );
        $ledger2 = $this->service->recordStockMovement($dto2);
        $this->assertEquals(70, $ledger2->running_balance);

        // Purchase 50 more units
        $dto3 = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 50,
            unitCost: 55.00
        );
        $ledger3 = $this->service->recordStockMovement($dto3);
        $this->assertEquals(120, $ledger3->running_balance);
    }

    /** @test */
    public function it_throws_exception_for_non_trackable_product()
    {
        $product = Product::factory()
            ->forTenant($this->tenant)
            ->service() // Service products don't track inventory
            ->create();

        $dto = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 100,
            unitCost: 50.00
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product does not require stock tracking');

        $this->service->recordStockMovement($dto);
    }

    /** @test */
    public function it_can_get_current_stock_balance()
    {
        $product = Product::factory()
            ->forTenant($this->tenant)
            ->inventory()
            ->create();

        // Add multiple stock movements
        $this->service->recordStockMovement(new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 100,
            unitCost: 50.00
        ));

        $this->service->recordStockMovement(new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::SALE,
            quantity: 25,
            unitCost: 75.00
        ));

        $this->service->recordStockMovement(new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 50,
            unitCost: 52.00
        ));

        $currentStock = $this->service->getCurrentStock($product->id);

        $this->assertEquals(125, $currentStock); // 100 - 25 + 50 = 125
    }

    /** @test */
    public function it_handles_batch_tracking()
    {
        $product = Product::factory()
            ->forTenant($this->tenant)
            ->inventory()
            ->withBatchTracking()
            ->create();

        $dto = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 100,
            unitCost: 50.00,
            batchNumber: 'BATCH-001',
            lotNumber: 'LOT-123'
        );

        $ledger = $this->service->recordStockMovement($dto);

        $this->assertEquals('BATCH-001', $ledger->batch_number);
        $this->assertEquals('LOT-123', $ledger->lot_number);
    }

    /** @test */
    public function it_handles_expiry_tracking()
    {
        $product = Product::factory()
            ->forTenant($this->tenant)
            ->inventory()
            ->withExpiryTracking()
            ->create();

        $manufacturingDate = now()->subMonths(2);
        $expiryDate = now()->addYear();

        $dto = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 100,
            unitCost: 50.00,
            manufacturingDate: $manufacturingDate,
            expiryDate: $expiryDate
        );

        $ledger = $this->service->recordStockMovement($dto);

        $this->assertNotNull($ledger->manufacturing_date);
        $this->assertNotNull($ledger->expiry_date);
        $this->assertEquals($manufacturingDate->toDateString(), $ledger->manufacturing_date->toDateString());
        $this->assertEquals($expiryDate->toDateString(), $ledger->expiry_date->toDateString());
    }

    /** @test */
    public function it_calculates_total_cost_correctly()
    {
        $product = Product::factory()
            ->forTenant($this->tenant)
            ->inventory()
            ->create();

        $dto = new StockMovementDTO(
            productId: $product->id,
            movementType: StockMovementType::PURCHASE,
            quantity: 50,
            unitCost: 75.50
        );

        $ledger = $this->service->recordStockMovement($dto);

        $expectedTotal = 50 * 75.50;
        $this->assertEquals($expectedTotal, $ledger->total_cost);
    }
}
