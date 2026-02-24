<?php

namespace Tests\Unit\Inventory;

use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Inventory\Application\Listeners\HandleGoodsReceivedListener;
use Modules\Inventory\Application\Listeners\HandlePosOrderPlacedListener;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\POS\Domain\Events\PosOrderPlaced;
use Modules\Purchase\Domain\Events\GoodsReceived;
use PHPUnit\Framework\TestCase;

class StockIntegrationListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // HandlePosOrderPlacedListener
    // =========================================================================

    public function test_pos_listener_skips_when_tenant_id_empty(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = new PosOrderPlaced('order-1', '', []);

        (new HandlePosOrderPlacedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1); // verified via shouldNotReceive in tearDown
    }

    public function test_pos_listener_skips_when_lines_empty(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = new PosOrderPlaced('order-1', 'tenant-1', []);

        (new HandlePosOrderPlacedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_pos_listener_skips_line_without_location_id(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = new PosOrderPlaced('order-1', 'tenant-1', [
            ['product_id' => 'prod-1', 'variant_id' => null, 'quantity' => '2', 'location_id' => null],
        ]);

        (new HandlePosOrderPlacedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_pos_listener_skips_line_without_product_id(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = new PosOrderPlaced('order-1', 'tenant-1', [
            ['product_id' => null, 'variant_id' => null, 'quantity' => '1', 'location_id' => 'loc-1'],
        ]);

        (new HandlePosOrderPlacedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_pos_listener_skips_zero_quantity_line(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = new PosOrderPlaced('order-1', 'tenant-1', [
            ['product_id' => 'prod-1', 'variant_id' => null, 'quantity' => '0', 'location_id' => 'loc-1'],
        ]);

        (new HandlePosOrderPlacedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_pos_listener_deducts_stock_and_creates_movement(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')
            ->once()
            ->withArgs(fn ($pid, $lid, $qty, $tid, $vid) =>
                $pid === 'prod-1'
                && $lid === 'loc-1'
                && $qty === '3.00000000'
                && $tid === 'tenant-1'
                && $vid === null
            );

        $moveRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($d) =>
                $d['type']             === 'delivery'
                && $d['product_id']    === 'prod-1'
                && $d['from_location_id'] === 'loc-1'
                && $d['qty']           === '3.00000000'
                && $d['reference_type'] === 'pos_order'
                && $d['reference_id']  === 'order-1'
                && $d['tenant_id']     === 'tenant-1'
            )
            ->andReturn((object) ['id' => 'mov-1']);

        $event = new PosOrderPlaced('order-1', 'tenant-1', [
            ['product_id' => 'prod-1', 'variant_id' => null, 'quantity' => '3.00000000', 'location_id' => 'loc-1'],
        ]);

        (new HandlePosOrderPlacedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1); // shouldReceive()->once() verified in tearDown
    }

    public function test_pos_listener_deducts_multiple_lines(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')->twice();
        $moveRepo->shouldReceive('create')->twice()->andReturn((object) ['id' => 'mov-x']);

        $event = new PosOrderPlaced('order-2', 'tenant-1', [
            ['product_id' => 'prod-1', 'variant_id' => null, 'quantity' => '1', 'location_id' => 'loc-1'],
            ['product_id' => 'prod-2', 'variant_id' => null, 'quantity' => '2', 'location_id' => 'loc-1'],
        ]);

        (new HandlePosOrderPlacedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_pos_listener_gracefully_skips_line_on_insufficient_stock(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        // Simulate insufficient stock error
        $service->shouldReceive('decrease')
            ->once()
            ->andThrow(new \RuntimeException('Insufficient stock.'));

        // Movement should NOT be created if decrease failed
        $moveRepo->shouldNotReceive('create');

        $event = new PosOrderPlaced('order-3', 'tenant-1', [
            ['product_id' => 'prod-1', 'variant_id' => null, 'quantity' => '99999', 'location_id' => 'loc-1'],
        ]);

        // Should not throw — graceful degradation
        (new HandlePosOrderPlacedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_pos_listener_includes_variant_id_when_set(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')
            ->once()
            ->withArgs(fn ($pid, $lid, $qty, $tid, $vid) => $vid === 'var-1');

        $moveRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($d) => $d['variant_id'] === 'var-1')
            ->andReturn((object) ['id' => 'mov-v1']);

        $event = new PosOrderPlaced('order-4', 'tenant-1', [
            ['product_id' => 'prod-1', 'variant_id' => 'var-1', 'quantity' => '1', 'location_id' => 'loc-1'],
        ]);

        (new HandlePosOrderPlacedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // HandleGoodsReceivedListener
    // =========================================================================

    public function test_grn_listener_skips_when_tenant_id_empty(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('increase');
        $moveRepo->shouldNotReceive('create');

        $event = new GoodsReceived('po-1', 'grn-1', '', []);

        (new HandleGoodsReceivedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_grn_listener_skips_when_lines_empty(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('increase');
        $moveRepo->shouldNotReceive('create');

        $event = new GoodsReceived('po-1', 'grn-1', 'tenant-1', []);

        (new HandleGoodsReceivedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_grn_listener_skips_line_with_zero_qty_accepted(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('increase');
        $moveRepo->shouldNotReceive('create');

        $event = new GoodsReceived('po-1', 'grn-1', 'tenant-1', [
            ['product_id' => 'prod-1', 'qty_accepted' => '0', 'location_id' => 'loc-1'],
        ]);

        (new HandleGoodsReceivedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_grn_listener_skips_line_without_location(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('increase');
        $moveRepo->shouldNotReceive('create');

        $event = new GoodsReceived('po-1', 'grn-1', 'tenant-1', [
            ['product_id' => 'prod-1', 'qty_accepted' => '5', 'location_id' => null],
        ]);

        (new HandleGoodsReceivedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_grn_listener_increases_stock_and_creates_movement(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('increase')
            ->once()
            ->withArgs(fn ($pid, $lid, $qty, $tid, $vid) =>
                $pid === 'prod-1'
                && $lid === 'loc-1'
                && $qty === '10.00000000'
                && $tid === 'tenant-1'
                && $vid === null
            );

        $moveRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($d) =>
                $d['type']            === 'receipt'
                && $d['product_id']   === 'prod-1'
                && $d['to_location_id'] === 'loc-1'
                && $d['qty']          === '10.00000000'
                && $d['reference_type'] === 'purchase_grn'
                && $d['reference_id'] === 'grn-1'
                && $d['tenant_id']    === 'tenant-1'
            )
            ->andReturn((object) ['id' => 'mov-2']);

        $event = new GoodsReceived('po-1', 'grn-1', 'tenant-1', [
            ['product_id' => 'prod-1', 'qty_accepted' => '10.00000000', 'location_id' => 'loc-1'],
        ]);

        (new HandleGoodsReceivedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_grn_listener_processes_multiple_lines(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('increase')->twice();
        $moveRepo->shouldReceive('create')->twice()->andReturn((object) ['id' => 'mov-x']);

        $event = new GoodsReceived('po-2', 'grn-2', 'tenant-1', [
            ['product_id' => 'prod-1', 'qty_accepted' => '5', 'location_id' => 'loc-1'],
            ['product_id' => 'prod-2', 'qty_accepted' => '3', 'location_id' => 'loc-1'],
        ]);

        (new HandleGoodsReceivedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_grn_listener_skips_rejected_lines_processes_accepted(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        // Only the accepted line should trigger increase
        $service->shouldReceive('increase')->once();
        $moveRepo->shouldReceive('create')->once()->andReturn((object) ['id' => 'mov-x']);

        $event = new GoodsReceived('po-3', 'grn-3', 'tenant-1', [
            ['product_id' => 'prod-1', 'qty_accepted' => '0', 'location_id' => 'loc-1'],  // rejected
            ['product_id' => 'prod-2', 'qty_accepted' => '7', 'location_id' => 'loc-1'],  // accepted
        ]);

        (new HandleGoodsReceivedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }
}
