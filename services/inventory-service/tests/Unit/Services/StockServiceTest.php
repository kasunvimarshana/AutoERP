<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\StockMovement;
use App\Services\StockService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

/**
 * Stock Service Unit Tests
 * Validates Saga participant stock operations.
 */
class StockServiceTest extends TestCase
{
    private StockService $service;
    private InventoryRepositoryInterface $inventoryRepo;
    private StockMovementRepositoryInterface $movementRepo;
    private MessageBrokerInterface $broker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryRepo = Mockery::mock(InventoryRepositoryInterface::class);
        $this->movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $this->broker = Mockery::mock(MessageBrokerInterface::class)->shouldIgnoreMissing();
        $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

        $this->service = new StockService(
            $this->inventoryRepo,
            $this->movementRepo,
            $this->broker,
            $logger
        );
    }

    #[Test]
    public function it_checks_availability_when_item_has_sufficient_stock(): void
    {
        $item = Mockery::mock(InventoryItem::class)->makePartial();
        $item->quantity_on_hand = 100;
        $item->quantity_reserved = 20;

        $this->inventoryRepo->shouldReceive('findByProductAndWarehouse')
            ->with('prod-1', 'wh-1')
            ->andReturn($item);

        $available = $this->service->checkAvailability('prod-1', 'wh-1', 50);

        $this->assertTrue($available);
    }

    #[Test]
    public function it_returns_false_when_item_not_found(): void
    {
        $this->inventoryRepo->shouldReceive('findByProductAndWarehouse')
            ->with('prod-1', 'wh-1')
            ->andReturn(null);

        $available = $this->service->checkAvailability('prod-1', 'wh-1', 10);

        $this->assertFalse($available);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
