<?php

namespace Tests\Unit\Inventory;

use Mockery;
use Modules\ECommerce\Domain\Events\ECommerceOrderConfirmed;
use Modules\Inventory\Application\Listeners\HandleECommerceOrderConfirmedListener;
use Modules\Inventory\Application\Services\StockLevelService;
use Modules\Inventory\Domain\Contracts\StockMovementRepositoryInterface;
use PHPUnit\Framework\TestCase;

class ECommerceOrderStockDeductionListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string $orderId  = 'eco-uuid-1',
        string $tenantId = 'tenant-1',
        array  $lines    = [],
    ): ECommerceOrderConfirmed {
        return new ECommerceOrderConfirmed(
            orderId:  $orderId,
            tenantId: $tenantId,
            lines:    $lines,
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

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

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

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip line without inventory_product_id
    // -------------------------------------------------------------------------

    public function test_skips_line_without_inventory_product_id(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        $event = $this->makeEvent(lines: [
            ['inventory_product_id' => null, 'qty' => '3', 'location_id' => 'loc-1'],
        ]);

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

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
            ['inventory_product_id' => 'inv-prod-1', 'qty' => '3', 'location_id' => null],
        ]);

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

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
            ['inventory_product_id' => 'inv-prod-1', 'qty' => '0', 'location_id' => 'loc-1'],
        ]);

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: deducts stock and creates sale movement for a single line
    // -------------------------------------------------------------------------

    public function test_deducts_stock_and_creates_sale_movement(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')
            ->once()
            ->with('inv-prod-1', 'loc-1', '2.00000000', 'tenant-1');

        $moveRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['tenant_id']        === 'tenant-1'
                    && $data['type']             === 'sale'
                    && $data['product_id']       === 'inv-prod-1'
                    && $data['from_location_id'] === 'loc-1'
                    && $data['qty']              === '2.00000000'
                    && $data['reference_type']   === 'ecommerce_order'
                    && $data['reference_id']     === 'eco-uuid-1';
            });

        $event = $this->makeEvent(lines: [
            ['inventory_product_id' => 'inv-prod-1', 'qty' => '2.00000000', 'location_id' => 'loc-1'],
        ]);

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

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
            ['inventory_product_id' => 'inv-prod-1', 'qty' => '1', 'location_id' => 'loc-1'],
            ['inventory_product_id' => 'inv-prod-2', 'qty' => '4', 'location_id' => 'loc-1'],
        ]);

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: stock error does not abort order confirmation
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_on_stock_error(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')
            ->once()
            ->andThrow(new \DomainException('Insufficient stock'));

        $moveRepo->shouldNotReceive('create');

        $event = $this->makeEvent(lines: [
            ['inventory_product_id' => 'inv-prod-1', 'qty' => '999', 'location_id' => 'loc-1'],
        ]);

        // Must not throw — graceful degradation
        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

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
        $service->shouldReceive('decrease')->once()->with('inv-prod-valid', 'loc-1', '3', 'tenant-1');
        $moveRepo->shouldReceive('create')->once();

        $event = $this->makeEvent(lines: [
            ['inventory_product_id' => null,             'qty' => '1', 'location_id' => 'loc-1'],   // no product
            ['inventory_product_id' => 'inv-prod-valid', 'qty' => '3', 'location_id' => 'loc-1'],   // valid
            ['inventory_product_id' => 'inv-prod-2',     'qty' => '0', 'location_id' => 'loc-1'],   // zero qty
            ['inventory_product_id' => 'inv-prod-3',     'qty' => '5', 'location_id' => null],      // no location
        ]);

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Backwards compatibility: old events with no lines are skipped
    // -------------------------------------------------------------------------

    public function test_backwards_compatible_event_with_no_lines_is_skipped(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldNotReceive('decrease');
        $moveRepo->shouldNotReceive('create');

        // Construct event the old way — lines defaults to []
        $event = new ECommerceOrderConfirmed('eco-old', 'tenant-1');

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Verify movement reference type is 'ecommerce_order'
    // -------------------------------------------------------------------------

    public function test_movement_reference_type_is_ecommerce_order(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')->once();

        $moveRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['reference_type'] === 'ecommerce_order'
                    && $data['reference_id']   === 'eco-ref-99';
            });

        $event = $this->makeEvent(orderId: 'eco-ref-99', lines: [
            ['inventory_product_id' => 'inv-prod-x', 'qty' => '1', 'location_id' => 'loc-2'],
        ]);

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Verify movement type is 'sale'
    // -------------------------------------------------------------------------

    public function test_movement_type_is_sale(): void
    {
        $service  = Mockery::mock(StockLevelService::class);
        $moveRepo = Mockery::mock(StockMovementRepositoryInterface::class);

        $service->shouldReceive('decrease')->once();

        $moveRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $data) {
                return $data['type'] === 'sale';
            });

        $event = $this->makeEvent(lines: [
            ['inventory_product_id' => 'inv-prod-1', 'qty' => '5', 'location_id' => 'loc-1'],
        ]);

        (new HandleECommerceOrderConfirmedListener($service, $moveRepo))->handle($event);

        $this->addToAssertionCount(1);
    }
}
