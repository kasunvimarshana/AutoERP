<?php

namespace Tests\Unit\Inventory;

use Mockery;
use Modules\Inventory\Application\Listeners\HandleWorkOrderCompletedListener;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Manufacturing\Domain\Events\WorkOrderCompleted;
use PHPUnit\Framework\TestCase;

class WorkOrderStockIntegrationListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Guard clauses
    // =========================================================================

    public function test_listener_skips_when_tenant_id_empty(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $service->shouldNotReceive('increase');
        $moveRepo->shouldNotReceive('create');

        $event = new WorkOrderCompleted('wo-1', '', '10');

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1); // verified via shouldNotReceive in tearDown
    }

    public function test_listener_skips_when_no_components_and_no_finished_product(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $service->shouldNotReceive('increase');
        $moveRepo->shouldNotReceive('create');

        $event = new WorkOrderCompleted('wo-1', 'tenant-1', '10');

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_listener_skips_component_without_product_id(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = new WorkOrderCompleted('wo-1', 'tenant-1', '10', null, null, [
            ['product_id' => null, 'qty_consumed' => '5', 'location_id' => 'loc-1'],
        ]);

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_listener_skips_component_without_location(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = new WorkOrderCompleted('wo-1', 'tenant-1', '10', null, null, [
            ['product_id' => 'comp-1', 'qty_consumed' => '5', 'location_id' => null],
        ]);

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_listener_skips_zero_qty_component(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = new WorkOrderCompleted('wo-1', 'tenant-1', '10', null, null, [
            ['product_id' => 'comp-1', 'qty_consumed' => '0', 'location_id' => 'loc-1'],
        ]);

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // Component stock deduction
    // =========================================================================

    public function test_listener_deducts_component_and_creates_consumption_movement(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')
            ->once()
            ->withArgs(fn ($pid, $lid, $qty, $tid) =>
                $pid === 'comp-1'
                && $lid === 'loc-1'
                && $qty === '3.00000000'
                && $tid === 'tenant-1'
            );

        $moveRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($d) =>
                $d['type']             === 'consumption'
                && $d['product_id']    === 'comp-1'
                && $d['from_location_id'] === 'loc-1'
                && $d['qty']           === '3.00000000'
                && $d['reference_type'] === 'work_order'
                && $d['reference_id']  === 'wo-1'
                && $d['tenant_id']     === 'tenant-1'
            )
            ->andReturn((object) ['id' => 'mov-1']);

        $event = new WorkOrderCompleted('wo-1', 'tenant-1', '10', null, null, [
            ['product_id' => 'comp-1', 'qty_consumed' => '3.00000000', 'location_id' => 'loc-1'],
        ]);

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_listener_deducts_multiple_components(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')->twice();
        $moveRepo->shouldReceive('create')->twice()->andReturn((object) ['id' => 'mov-x']);

        $event = new WorkOrderCompleted('wo-2', 'tenant-1', '5', null, null, [
            ['product_id' => 'comp-1', 'qty_consumed' => '2', 'location_id' => 'loc-1'],
            ['product_id' => 'comp-2', 'qty_consumed' => '4', 'location_id' => 'loc-1'],
        ]);

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_listener_graceful_degradation_on_insufficient_component_stock(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')
            ->once()
            ->andThrow(new \RuntimeException('Insufficient stock.'));

        // Movement must NOT be created if decrease failed
        $moveRepo->shouldNotReceive('create');

        $event = new WorkOrderCompleted('wo-3', 'tenant-1', '1', null, null, [
            ['product_id' => 'comp-1', 'qty_consumed' => '99999', 'location_id' => 'loc-1'],
        ]);

        // Must not throw — graceful degradation
        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // Finished goods receipt
    // =========================================================================

    public function test_listener_receives_finished_goods_when_product_and_location_set(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease'); // no components
        $service->shouldReceive('increase')
            ->once()
            ->withArgs(fn ($pid, $lid, $qty, $tid) =>
                $pid === 'finished-1'
                && $lid === 'loc-fg'
                && $qty === '10'
                && $tid === 'tenant-1'
            );

        $moveRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($d) =>
                $d['type']           === 'production'
                && $d['product_id']  === 'finished-1'
                && $d['to_location_id'] === 'loc-fg'
                && $d['qty']         === '10'
                && $d['reference_type'] === 'work_order'
                && $d['reference_id'] === 'wo-4'
                && $d['tenant_id']   === 'tenant-1'
            )
            ->andReturn((object) ['id' => 'mov-fg']);

        $event = new WorkOrderCompleted('wo-4', 'tenant-1', '10', 'finished-1', 'loc-fg');

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_listener_skips_finished_goods_when_product_id_missing(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('increase');
        $moveRepo->shouldNotReceive('create');

        $event = new WorkOrderCompleted('wo-5', 'tenant-1', '10', null, 'loc-fg');

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_listener_skips_finished_goods_when_location_missing(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('increase');
        $moveRepo->shouldNotReceive('create');

        $event = new WorkOrderCompleted('wo-6', 'tenant-1', '10', 'finished-1', null);

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_listener_processes_components_and_receives_finished_goods(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        // 2 component deductions + 1 finished goods receipt
        $service->shouldReceive('decrease')->twice();
        $service->shouldReceive('increase')->once();
        $moveRepo->shouldReceive('create')->times(3)->andReturn((object) ['id' => 'mov-x']);

        $event = new WorkOrderCompleted('wo-7', 'tenant-1', '5', 'finished-1', 'loc-fg', [
            ['product_id' => 'comp-1', 'qty_consumed' => '10', 'location_id' => 'loc-raw'],
            ['product_id' => 'comp-2', 'qty_consumed' => '2',  'location_id' => 'loc-raw'],
        ]);

        (new HandleWorkOrderCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }
}
