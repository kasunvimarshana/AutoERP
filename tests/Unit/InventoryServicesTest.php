<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Modules\Inventory\Application\Contracts\AllocateStockServiceInterface;
use Modules\Inventory\Application\Contracts\IssueStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveStockServiceInterface;
use Modules\Inventory\Application\Services\AddValuationLayerService;
use Modules\Inventory\Application\Services\AllocateStockService;
use Modules\Inventory\Application\Services\ConsumeValuationLayersService;
use Modules\Inventory\Application\Services\CreateCycleCountService;
use Modules\Inventory\Application\Services\InventoryManagerService;
use Modules\Inventory\Application\Services\ReserveStockService;
use Modules\Inventory\Application\Services\ReleaseStockService;
use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;

class InventoryServicesTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // AddValuationLayerService
    // ──────────────────────────────────────────────────────────────────────

    private function makeLayer(float $qty = 100.0, float $cost = 5.0): InventoryValuationLayer
    {
        return new InventoryValuationLayer(1, 1, 1, 1, $qty, $qty, $cost, new \DateTimeImmutable(), 'REF-001', null, null, null);
    }

    public function test_add_valuation_layer_creates_layer(): void
    {
        /** @var InventoryValuationLayerRepositoryInterface&MockObject $layerRepo */
        $layerRepo = $this->createMock(InventoryValuationLayerRepositoryInterface::class);
        $expected  = $this->makeLayer(50.0, 10.0);

        $layerRepo->expects($this->once())
            ->method('create')
            ->willReturn($expected);

        $service = new AddValuationLayerService($layerRepo);
        $result  = $service->execute(1, 1, 1, 50.0, 10.0, 'GR-001');

        $this->assertEquals(50.0, $result->getQuantityRemaining());
        $this->assertEquals(10.0, $result->getUnitCost());
    }

    public function test_add_valuation_layer_rejects_zero_quantity(): void
    {
        $layerRepo = $this->createMock(InventoryValuationLayerRepositoryInterface::class);
        $service   = new AddValuationLayerService($layerRepo);

        $this->expectException(\InvalidArgumentException::class);
        $service->execute(1, 1, 1, 0.0, 10.0, 'GR-001');
    }

    public function test_add_valuation_layer_rejects_negative_quantity(): void
    {
        $layerRepo = $this->createMock(InventoryValuationLayerRepositoryInterface::class);
        $service   = new AddValuationLayerService($layerRepo);

        $this->expectException(\InvalidArgumentException::class);
        $service->execute(1, 1, 1, -5.0, 10.0, 'GR-001');
    }

    // ──────────────────────────────────────────────────────────────────────
    // ConsumeValuationLayersService
    // ──────────────────────────────────────────────────────────────────────

    public function test_consume_valuation_layers_fifo(): void
    {
        $layer1 = new InventoryValuationLayer(1, 1, 1, 1, 50.0, 50.0, 10.0, new \DateTimeImmutable('2024-01-01'), null, null, null, null);
        $layer2 = new InventoryValuationLayer(2, 1, 1, 1, 50.0, 50.0, 12.0, new \DateTimeImmutable('2024-01-02'), null, null, null, null);

        $layerRepo = $this->createMock(InventoryValuationLayerRepositoryInterface::class);
        $layerRepo->method('findLayersForConsumption')->willReturn([$layer1, $layer2]);
        $layerRepo->method('update')->willReturn(null);

        $service = new ConsumeValuationLayersService($layerRepo);
        // Consuming 75 units: 50 @ $10 + 25 @ $12 = $800 / 75 = $10.67
        $avgCost = $service->execute(1, 1, 1, 75.0, 'fifo');

        $this->assertEqualsWithDelta(10.666, $avgCost, 0.001);
        $this->assertEquals(0.0, $layer1->getQuantityRemaining());
        $this->assertEquals(25.0, $layer2->getQuantityRemaining());
    }

    public function test_consume_valuation_layers_rejects_zero_quantity(): void
    {
        $layerRepo = $this->createMock(InventoryValuationLayerRepositoryInterface::class);
        $service   = new ConsumeValuationLayersService($layerRepo);

        $this->expectException(\InvalidArgumentException::class);
        $service->execute(1, 1, 1, 0.0, 'fifo');
    }

    public function test_consume_valuation_layers_throws_on_insufficient(): void
    {
        $layer = new InventoryValuationLayer(1, 1, 1, 1, 10.0, 10.0, 5.0, new \DateTimeImmutable(), null, null, null, null);

        $layerRepo = $this->createMock(InventoryValuationLayerRepositoryInterface::class);
        $layerRepo->method('findLayersForConsumption')->willReturn([$layer]);
        $layerRepo->method('update')->willReturn(null);

        $service = new ConsumeValuationLayersService($layerRepo);

        $this->expectException(\DomainException::class);
        $service->execute(1, 1, 1, 50.0, 'fifo'); // only 10 available
    }

    // ──────────────────────────────────────────────────────────────────────
    // AllocateStockService
    // ──────────────────────────────────────────────────────────────────────

    private function makeLevel(float $onHand = 100.0, float $reserved = 0.0): InventoryLevel
    {
        return new InventoryLevel(1, 1, 1, 1, null, $onHand, $reserved, 0.0, 'fifo', null, null);
    }

    private function makeBatch(int $id, float $remaining): InventoryBatch
    {
        return new InventoryBatch(
            $id, 1, 1, 1, "BATCH-{$id}", null, null,
            $remaining, $remaining, 10.0, null, null,
            new \DateTimeImmutable(), 'active', null, null, null,
        );
    }

    public function test_allocate_stock_returns_batch_allocations(): void
    {
        $level  = $this->makeLevel(100.0, 0.0);
        $batch1 = $this->makeBatch(1, 60.0);
        $batch2 = $this->makeBatch(2, 60.0);

        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $levelRepo->method('findByProduct')->willReturn($level);

        $batchRepo = $this->createMock(InventoryBatchRepositoryInterface::class);
        $batchRepo->method('findActiveBatches')->willReturn([$batch1, $batch2]);

        $service     = new AllocateStockService($levelRepo, $batchRepo);
        $allocations = $service->execute(1, 1, 1, 80.0, 'fifo');

        $this->assertCount(2, $allocations);
        $this->assertEquals(60.0, $allocations[0]['quantity']);
        $this->assertEquals(20.0, $allocations[1]['quantity']);
    }

    public function test_allocate_stock_no_batches_allocates_from_level(): void
    {
        $level = $this->makeLevel(100.0, 0.0);

        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $levelRepo->method('findByProduct')->willReturn($level);

        $batchRepo = $this->createMock(InventoryBatchRepositoryInterface::class);
        $batchRepo->method('findActiveBatches')->willReturn([]);

        $service     = new AllocateStockService($levelRepo, $batchRepo);
        $allocations = $service->execute(1, 1, 1, 50.0, 'fifo');

        $this->assertCount(1, $allocations);
        $this->assertNull($allocations[0]['batch_id']);
        $this->assertEquals(50.0, $allocations[0]['quantity']);
    }

    public function test_allocate_stock_throws_on_insufficient_stock(): void
    {
        $level = $this->makeLevel(30.0, 0.0);  // only 30 available

        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $levelRepo->method('findByProduct')->willReturn($level);

        $batchRepo = $this->createMock(InventoryBatchRepositoryInterface::class);
        $batchRepo->method('findActiveBatches')->willReturn([]);

        $service = new AllocateStockService($levelRepo, $batchRepo);

        $this->expectException(\DomainException::class);
        $service->execute(1, 1, 1, 50.0, 'fifo');
    }

    public function test_allocate_stock_throws_on_zero_quantity(): void
    {
        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $batchRepo = $this->createMock(InventoryBatchRepositoryInterface::class);

        $service = new AllocateStockService($levelRepo, $batchRepo);

        $this->expectException(\InvalidArgumentException::class);
        $service->execute(1, 1, 1, 0.0, 'fifo');
    }

    // ──────────────────────────────────────────────────────────────────────
    // ReserveStockService
    // ──────────────────────────────────────────────────────────────────────

    public function test_reserve_stock_service_reserves_successfully(): void
    {
        $level = $this->makeLevel(100.0, 0.0);

        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $levelRepo->method('findByProduct')->willReturn($level);
        $levelRepo->expects($this->once())->method('update');

        $service = new ReserveStockService($levelRepo);
        $result  = $service->execute(1, 1, 1, 30.0);

        $this->assertEquals(30.0, $result->getQuantityReserved());
        $this->assertEquals(70.0, $result->getAvailableQuantity());
    }

    public function test_reserve_stock_service_throws_when_level_not_found(): void
    {
        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $levelRepo->method('findByProduct')->willReturn(null);

        $service = new ReserveStockService($levelRepo);
        $this->expectException(InsufficientStockException::class);
        $service->execute(1, 1, 1, 10.0);
    }

    public function test_reserve_stock_service_throws_when_insufficient_available(): void
    {
        $level = $this->makeLevel(20.0, 15.0);  // available = 5

        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $levelRepo->method('findByProduct')->willReturn($level);

        $service = new ReserveStockService($levelRepo);
        $this->expectException(\DomainException::class);
        $service->execute(1, 1, 1, 10.0);  // wants 10, only 5 available
    }

    // ──────────────────────────────────────────────────────────────────────
    // ReleaseStockService
    // ──────────────────────────────────────────────────────────────────────

    public function test_release_stock_service_releases_reservation(): void
    {
        $level = $this->makeLevel(100.0, 50.0);

        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $levelRepo->method('findByProduct')->willReturn($level);
        $levelRepo->expects($this->once())->method('update');

        $service = new ReleaseStockService($levelRepo);
        $result  = $service->execute(1, 1, 1, 30.0);

        $this->assertEquals(20.0, $result->getQuantityReserved());
    }

    public function test_release_stock_service_clamps_reservation_to_zero(): void
    {
        $level = $this->makeLevel(100.0, 10.0);

        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $levelRepo->method('findByProduct')->willReturn($level);
        $levelRepo->method('update');

        $service = new ReleaseStockService($levelRepo);
        $result  = $service->execute(1, 1, 1, 50.0);  // release more than reserved

        $this->assertEquals(0.0, $result->getQuantityReserved());
    }

    public function test_release_stock_service_throws_when_level_not_found(): void
    {
        $levelRepo = $this->createMock(InventoryLevelRepositoryInterface::class);
        $levelRepo->method('findByProduct')->willReturn(null);

        $service = new ReleaseStockService($levelRepo);
        $this->expectException(InsufficientStockException::class);
        $service->execute(1, 1, 1, 10.0);
    }

    // ──────────────────────────────────────────────────────────────────────
    // InventoryCycleCount entity
    // ──────────────────────────────────────────────────────────────────────

    private function makeCycleCount(string $status = 'pending'): InventoryCycleCount
    {
        return new InventoryCycleCount(
            1, 1, 1, null, $status, null, null, null, 'Annual count', null, null,
        );
    }

    public function test_cycle_count_creation(): void
    {
        $count = $this->makeCycleCount();
        $this->assertEquals(1, $count->getId());
        $this->assertEquals(1, $count->getTenantId());
        $this->assertEquals(1, $count->getWarehouseId());
        $this->assertNull($count->getProductId());
        $this->assertEquals('pending', $count->getStatus());
        $this->assertEquals('Annual count', $count->getNotes());
        $this->assertNull($count->getCountedBy());
    }

    public function test_cycle_count_start_transitions_to_in_progress(): void
    {
        $count = $this->makeCycleCount();
        $count->start(42);
        $this->assertEquals('in_progress', $count->getStatus());
        $this->assertEquals(42, $count->getCountedBy());
        $this->assertNotNull($count->getStartedAt());
    }

    public function test_cycle_count_start_fails_if_not_pending(): void
    {
        $count = $this->makeCycleCount('in_progress');
        $this->expectException(\DomainException::class);
        $count->start(42);
    }

    public function test_cycle_count_complete_transitions_from_in_progress(): void
    {
        $count = $this->makeCycleCount('in_progress');
        $count->complete();
        $this->assertEquals('completed', $count->getStatus());
        $this->assertNotNull($count->getCompletedAt());
    }

    public function test_cycle_count_complete_fails_if_pending(): void
    {
        $count = $this->makeCycleCount('pending');
        $this->expectException(\DomainException::class);
        $count->complete();
    }

    public function test_cycle_count_cancel_from_pending(): void
    {
        $count = $this->makeCycleCount('pending');
        $count->cancel();
        $this->assertEquals('cancelled', $count->getStatus());
    }

    public function test_cycle_count_cancel_from_in_progress(): void
    {
        $count = $this->makeCycleCount('in_progress');
        $count->cancel();
        $this->assertEquals('cancelled', $count->getStatus());
    }

    public function test_cycle_count_cancel_fails_if_already_completed(): void
    {
        $count = $this->makeCycleCount('completed');
        $this->expectException(\DomainException::class);
        $count->cancel();
    }

    public function test_cycle_count_full_lifecycle(): void
    {
        $count = $this->makeCycleCount();
        $count->start(7);
        $count->complete();

        $this->assertEquals('completed', $count->getStatus());
        $this->assertEquals(7, $count->getCountedBy());
        $this->assertNotNull($count->getStartedAt());
        $this->assertNotNull($count->getCompletedAt());
    }

    // ──────────────────────────────────────────────────────────────────────
    // CreateCycleCountService
    // ──────────────────────────────────────────────────────────────────────

    public function test_create_cycle_count_creates_pending_record(): void
    {
        $expected = new InventoryCycleCount(10, 1, 2, null, 'pending', null, null, null, 'Q1 count', null, null);

        /** @var InventoryCycleCountRepositoryInterface&MockObject $cycleRepo */
        $cycleRepo = $this->createMock(InventoryCycleCountRepositoryInterface::class);
        $cycleRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return $data['tenant_id'] === 1
                    && $data['warehouse_id'] === 2
                    && $data['status'] === 'pending'
                    && $data['product_id'] === null
                    && $data['notes'] === 'Q1 count';
            }))
            ->willReturn($expected);

        $service = new CreateCycleCountService($cycleRepo);
        $result  = $service->execute(1, 2, null, 'Q1 count');

        $this->assertEquals(10, $result->getId());
        $this->assertEquals('pending', $result->getStatus());
        $this->assertEquals('Q1 count', $result->getNotes());
    }

    public function test_create_cycle_count_with_product_filter(): void
    {
        $expected = new InventoryCycleCount(11, 1, 2, 5, 'pending', null, null, null, null, null, null);

        /** @var InventoryCycleCountRepositoryInterface&MockObject $cycleRepo */
        $cycleRepo = $this->createMock(InventoryCycleCountRepositoryInterface::class);
        $cycleRepo->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return $data['product_id'] === 5;
            }))
            ->willReturn($expected);

        $service = new CreateCycleCountService($cycleRepo);
        $result  = $service->execute(1, 2, 5);

        $this->assertEquals(5, $result->getProductId());
    }

    public function test_create_cycle_count_rejects_invalid_warehouse(): void
    {
        /** @var InventoryCycleCountRepositoryInterface&MockObject $cycleRepo */
        $cycleRepo = $this->createMock(InventoryCycleCountRepositoryInterface::class);
        $cycleRepo->expects($this->never())->method('create');

        $service = new CreateCycleCountService($cycleRepo);
        $this->expectException(\InvalidArgumentException::class);
        $service->execute(1, 0);
    }

    // ──────────────────────────────────────────────────────────────────────
    // InventoryManagerService
    // ──────────────────────────────────────────────────────────────────────

    public function test_inventory_manager_allocate_stock_delegates_to_allocate_service(): void
    {
        $expected = [['batch_id' => 1, 'quantity' => 30.0, 'expires_at' => null]];

        /** @var AllocateStockServiceInterface&MockObject $allocateSvc */
        $allocateSvc = $this->createMock(AllocateStockServiceInterface::class);
        $allocateSvc->expects($this->once())
            ->method('execute')
            ->with(1, 2, 3, 30.0, 'fefo')
            ->willReturn($expected);

        $receiveSvc = $this->createMock(ReceiveStockServiceInterface::class);
        $issueSvc   = $this->createMock(IssueStockServiceInterface::class);

        $manager = new InventoryManagerService($receiveSvc, $issueSvc, $allocateSvc);
        $result  = $manager->allocateStock(1, 2, 3, 30.0, 'fefo');

        $this->assertSame($expected, $result);
    }

    public function test_inventory_manager_allocate_uses_fefo_by_default(): void
    {
        /** @var AllocateStockServiceInterface&MockObject $allocateSvc */
        $allocateSvc = $this->createMock(AllocateStockServiceInterface::class);
        $allocateSvc->expects($this->once())
            ->method('execute')
            ->with(1, 1, 1, 10.0, 'fefo')
            ->willReturn([]);

        $manager = new InventoryManagerService(
            $this->createMock(ReceiveStockServiceInterface::class),
            $this->createMock(IssueStockServiceInterface::class),
            $allocateSvc,
        );

        $manager->allocateStock(1, 1, 1, 10.0);
    }

    public function test_inventory_manager_receive_stock_delegates_to_receive_service(): void
    {
        $level = $this->makeLevel(150.0);

        /** @var ReceiveStockServiceInterface&MockObject $receiveSvc */
        $receiveSvc = $this->createMock(ReceiveStockServiceInterface::class);
        $receiveSvc->expects($this->once())
            ->method('execute')
            ->willReturn($level);

        $manager = new InventoryManagerService(
            $receiveSvc,
            $this->createMock(IssueStockServiceInterface::class),
            $this->createMock(AllocateStockServiceInterface::class),
        );

        $manager->receiveStock(1, 1, 1, 50.0, 10.0);
    }

    public function test_inventory_manager_issue_stock_delegates_to_issue_service(): void
    {
        $level = $this->makeLevel(70.0);

        /** @var IssueStockServiceInterface&MockObject $issueSvc */
        $issueSvc = $this->createMock(IssueStockServiceInterface::class);
        $issueSvc->expects($this->once())
            ->method('execute')
            ->willReturn($level);

        $manager = new InventoryManagerService(
            $this->createMock(ReceiveStockServiceInterface::class),
            $issueSvc,
            $this->createMock(AllocateStockServiceInterface::class),
        );

        $manager->issueStock(1, 1, 1, 30.0);
    }

    public function test_inventory_manager_issue_stock_uses_fefo_by_default(): void
    {
        $level = $this->makeLevel(70.0);

        /** @var IssueStockServiceInterface&MockObject $issueSvc */
        $issueSvc = $this->createMock(IssueStockServiceInterface::class);
        $issueSvc->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($dto): bool {
                return $dto->allocation_strategy === 'fefo';
            }))
            ->willReturn($level);

        $manager = new InventoryManagerService(
            $this->createMock(ReceiveStockServiceInterface::class),
            $issueSvc,
            $this->createMock(AllocateStockServiceInterface::class),
        );

        $manager->issueStock(1, 1, 1, 30.0);
    }

    public function test_inventory_manager_issue_stock_accepts_custom_strategy(): void
    {
        $level = $this->makeLevel(70.0);

        /** @var IssueStockServiceInterface&MockObject $issueSvc */
        $issueSvc = $this->createMock(IssueStockServiceInterface::class);
        $issueSvc->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($dto): bool {
                return $dto->allocation_strategy === 'lifo';
            }))
            ->willReturn($level);

        $manager = new InventoryManagerService(
            $this->createMock(ReceiveStockServiceInterface::class),
            $issueSvc,
            $this->createMock(AllocateStockServiceInterface::class),
        );

        $manager->issueStock(1, 1, 1, 30.0, null, 'lifo');
    }
}
