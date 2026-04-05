<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\StockMovement\Application\Services\TransferStockService;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class StockMovementTransferTest extends TestCase
{
    private function makeLevel(int $warehouseId, float $onHand = 100.0, float $reserved = 0.0): InventoryLevel
    {
        return new InventoryLevel(
            $warehouseId, 1, 1, $warehouseId, null,
            $onHand, $reserved, 0.0, 'fifo', null, null
        );
    }

    public function test_transfer_stock_validates_positive_quantity(): void
    {
        $movementRepo = $this->createMock(StockMovementRepositoryInterface::class);
        $levelRepo    = $this->createMock(InventoryLevelRepositoryInterface::class);

        $service = new TransferStockService($movementRepo, $levelRepo);

        $this->expectException(\InvalidArgumentException::class);
        $service->execute(1, 1, 1, 2, 0.0, 10.0, 'TRF', 1);
    }

    public function test_transfer_stock_requires_different_warehouses(): void
    {
        $movementRepo = $this->createMock(StockMovementRepositoryInterface::class);
        $levelRepo    = $this->createMock(InventoryLevelRepositoryInterface::class);

        $service = new TransferStockService($movementRepo, $levelRepo);

        $this->expectException(\InvalidArgumentException::class);
        $service->execute(1, 1, 1, 1, 50.0, 10.0, 'TRF', 1); // same from/to warehouse
    }

    public function test_stock_movement_transfer_type_constant(): void
    {
        $this->assertEquals('transfer', StockMovement::TYPE_TRANSFER);
    }

    public function test_transfer_service_can_be_instantiated(): void
    {
        $movementRepo = $this->createMock(StockMovementRepositoryInterface::class);
        $levelRepo    = $this->createMock(InventoryLevelRepositoryInterface::class);

        $service = new TransferStockService($movementRepo, $levelRepo);
        $this->assertInstanceOf(TransferStockService::class, $service);
    }

    public function test_source_level_updated_after_transfer(): void
    {
        // Test the domain-level stock deduction directly (without DB transaction)
        $sourceLevel = $this->makeLevel(1, 100.0);
        $sourceLevel->issue(50.0);  // simulates what TransferStockService does internally

        $this->assertEquals(50.0, $sourceLevel->getQuantityOnHand());
    }

    public function test_destination_level_updated_after_transfer(): void
    {
        // Test the domain-level stock receipt directly
        $destLevel = $this->makeLevel(2, 0.0);
        $destLevel->receive(50.0);  // simulates what TransferStockService does internally

        $this->assertEquals(50.0, $destLevel->getQuantityOnHand());
    }

    public function test_transfer_insufficient_stock_domain_exception(): void
    {
        // Domain entity enforces insufficient stock rules
        $sourceLevel = $this->makeLevel(1, 30.0);

        $this->expectException(\DomainException::class);
        $sourceLevel->issue(50.0);  // only 30 available
    }

    // ──────────────────────────────────────────────────────────────────────
    // StockMovement entity – comprehensive tests
    // ──────────────────────────────────────────────────────────────────────

    private function makeStockMovement(string $type = StockMovement::TYPE_TRANSFER): StockMovement
    {
        return new StockMovement(
            1, 1, 100, 1, null, null,
            $type, 50.0, 10.0, 'TRF-001', 'Transfer note', 7,
            new \DateTimeImmutable(), null, null,
        );
    }

    public function test_stock_movement_all_type_constants(): void
    {
        $this->assertEquals('receipt',    StockMovement::TYPE_RECEIPT);
        $this->assertEquals('issue',      StockMovement::TYPE_ISSUE);
        $this->assertEquals('transfer',   StockMovement::TYPE_TRANSFER);
        $this->assertEquals('adjustment', StockMovement::TYPE_ADJUSTMENT);
        $this->assertEquals('return',     StockMovement::TYPE_RETURN);
    }

    public function test_stock_movement_getters(): void
    {
        $mv = $this->makeStockMovement();
        $this->assertEquals(1, $mv->getId());
        $this->assertEquals(1, $mv->getTenantId());
        $this->assertEquals(100, $mv->getProductId());
        $this->assertEquals(1, $mv->getWarehouseId());
        $this->assertEquals('transfer', $mv->getMovementType());
        $this->assertEquals(50.0, $mv->getQuantity());
        $this->assertEquals(10.0, $mv->getUnitCost());
        $this->assertEquals('TRF-001', $mv->getReference());
        $this->assertEquals('Transfer note', $mv->getNotes());
        $this->assertEquals(7, $mv->getCreatedBy());
        $this->assertNotNull($mv->getMovedAt());
    }

    public function test_stock_movement_receipt_type(): void
    {
        $mv = $this->makeStockMovement(StockMovement::TYPE_RECEIPT);
        $this->assertEquals('receipt', $mv->getMovementType());
    }

    public function test_stock_movement_issue_type(): void
    {
        $mv = $this->makeStockMovement(StockMovement::TYPE_ISSUE);
        $this->assertEquals('issue', $mv->getMovementType());
    }

    public function test_stock_movement_adjustment_type(): void
    {
        $mv = $this->makeStockMovement(StockMovement::TYPE_ADJUSTMENT);
        $this->assertEquals('adjustment', $mv->getMovementType());
    }

    public function test_stock_movement_optional_location_fields(): void
    {
        $mv = new StockMovement(
            null, 1, 1, 1, 2, 3, StockMovement::TYPE_TRANSFER,
            20.0, 5.0, null, null, null, null, null, null,
        );
        $this->assertNull($mv->getId());
        $this->assertEquals(2, $mv->getFromLocationId());
        $this->assertEquals(3, $mv->getToLocationId());
        $this->assertNull($mv->getReference());
    }
}
