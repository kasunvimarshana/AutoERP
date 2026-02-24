<?php

namespace Tests\Unit\Inventory;

use Mockery;
use Modules\Inventory\Application\Listeners\HandleDeliveryCompletedListener;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use Modules\Logistics\Domain\Events\DeliveryCompleted;
use PHPUnit\Framework\TestCase;

class DeliveryStockIntegrationListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string $deliveryOrderId = 'do-uuid-1',
        string $tenantId = 'tenant-1',
        array  $lines = [],
    ): DeliveryCompleted {
        return new DeliveryCompleted(
            deliveryOrderId: $deliveryOrderId,
            tenantId: $tenantId,
            lines: $lines,
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = $this->makeEvent(tenantId: '');

        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when lines array is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_lines_empty(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = $this->makeEvent(lines: []);

        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip line without product_id
    // -------------------------------------------------------------------------

    public function test_skips_line_without_product_id(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = $this->makeEvent(lines: [
            ['product_id' => null, 'qty' => '3', 'location_id' => 'loc-1'],
        ]);

        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip line without location_id
    // -------------------------------------------------------------------------

    public function test_skips_line_without_location_id(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty' => '3', 'location_id' => null],
        ]);

        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip line with zero qty
    // -------------------------------------------------------------------------

    public function test_skips_line_with_zero_qty(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty' => '0', 'location_id' => 'loc-1'],
        ]);

        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: deducts stock and creates delivery movement for a single line
    // -------------------------------------------------------------------------

    public function test_deducts_stock_and_creates_delivery_movement(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')
            ->once()
            ->with('prod-1', 'loc-1', '5.00000000', 'tenant-1');

        $moveRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['tenant_id']        === 'tenant-1'
                    && $data['type']             === 'delivery'
                    && $data['product_id']       === 'prod-1'
                    && $data['from_location_id'] === 'loc-1'
                    && $data['qty']              === '5.00000000'
                    && $data['reference_type']   === 'delivery_order'
                    && $data['reference_id']     === 'do-uuid-1';
            });

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty' => '5.00000000', 'location_id' => 'loc-1'],
        ]);

        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: deducts stock for multiple lines
    // -------------------------------------------------------------------------

    public function test_deducts_stock_for_multiple_lines(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')->twice();
        $moveRepo->shouldReceive('create')->twice();

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty' => '3', 'location_id' => 'loc-1'],
            ['product_id' => 'prod-2', 'qty' => '7', 'location_id' => 'loc-1'],
        ]);

        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: insufficient stock does not abort delivery
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_on_insufficient_stock(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')
            ->once()
            ->andThrow(new \DomainException('Insufficient stock'));

        $moveRepo->shouldNotReceive('create');

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'qty' => '999', 'location_id' => 'loc-1'],
        ]);

        // Must not throw — graceful degradation
        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Mixed: processes valid lines and skips invalid ones
    // -------------------------------------------------------------------------

    public function test_processes_valid_lines_and_skips_invalid(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        // Only the one valid line should trigger decrease + create
        $service->shouldReceive('decrease')->once()->with('prod-valid', 'loc-1', '2', 'tenant-1');
        $moveRepo->shouldReceive('create')->once();

        $event = $this->makeEvent(lines: [
            ['product_id' => null,         'qty' => '1', 'location_id' => 'loc-1'],   // no product_id
            ['product_id' => 'prod-valid', 'qty' => '2', 'location_id' => 'loc-1'],   // valid
            ['product_id' => 'prod-2',     'qty' => '0', 'location_id' => 'loc-1'],   // zero qty
            ['product_id' => 'prod-3',     'qty' => '5', 'location_id' => null],      // no location
        ]);

        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Backwards compatibility: existing callers with no lines are skipped
    // -------------------------------------------------------------------------

    public function test_backwards_compatible_event_with_no_lines_is_skipped(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        // Construct event the old way — lines defaults to []
        $event = new DeliveryCompleted('do-old', 'tenant-1');

        (new HandleDeliveryCompletedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }
}
