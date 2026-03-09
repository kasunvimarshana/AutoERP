<?php
namespace Tests\Unit\Services;
use Tests\TestCase;
use App\Services\StockService;
use App\Domain\Contracts\StockRepositoryInterface;
use App\Domain\Contracts\StockMovementRepositoryInterface;
use App\Services\EventPublisherService;
use App\Domain\Models\StockLevel;
use App\Domain\Models\StockMovement;
use Mockery;

class StockServiceTest extends TestCase
{
    private StockRepositoryInterface         $stockRepo;
    private StockMovementRepositoryInterface $movementRepo;
    private EventPublisherService            $publisher;
    private StockService                     $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockRepo    = Mockery::mock(StockRepositoryInterface::class);
        $this->movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $this->publisher    = Mockery::mock(EventPublisherService::class);
        $this->stockService = new StockService($this->stockRepo, $this->movementRepo, $this->publisher);
    }

    public function test_get_stock_level_creates_if_not_exists(): void
    {
        $level = new StockLevel(['tenant_id' => 't1', 'product_id' => 'p1', 'warehouse_id' => 'w1']);
        $this->stockRepo->shouldReceive('getOrCreateStockLevel')->once()->with('t1', 'p1', 'w1')->andReturn($level);
        $result = $this->stockService->getStockLevel('t1', 'p1', 'w1');
        $this->assertSame($level, $result);
    }

    public function test_expire_reservations_returns_count(): void
    {
        $this->stockRepo->shouldReceive('getExpiredReservations')->once()->andReturn(collect());
        $count = $this->stockService->expireReservations();
        $this->assertEquals(0, $count);
    }

    protected function tearDown(): void { Mockery::close(); parent::tearDown(); }
}
