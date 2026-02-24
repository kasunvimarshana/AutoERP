<?php

namespace Tests\Unit\Logistics;

use Illuminate\Support\Facades\DB;
use Mockery;
use Modules\Logistics\Application\Listeners\HandleSalesOrderConfirmedListener;
use Modules\Logistics\Application\UseCases\CreateDeliveryOrderUseCase;
use Modules\Sales\Domain\Events\OrderConfirmed;
use PHPUnit\Framework\TestCase;

class SalesOrderDeliveryIntegrationListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string $orderId = 'so-uuid-1',
        string $tenantId = 'tenant-1',
        ?string $customerId = 'cust-1',
        ?string $promisedDate = '2026-03-01',
        array $lines = [],
    ): OrderConfirmed {
        return new OrderConfirmed(
            orderId: $orderId,
            tenantId: $tenantId,
            customerId: $customerId,
            promisedDeliveryDate: $promisedDate,
            lines: $lines,
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(tenantId: '');

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when no valid lines
    // -------------------------------------------------------------------------

    public function test_skips_when_lines_empty(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: []);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_line_without_product_id(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => null, 'description' => 'Item', 'qty' => '1', 'uom' => 'pcs'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_line_with_zero_qty(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Item', 'qty' => '0', 'uom' => 'pcs'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_skips_when_all_lines_invalid(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(lines: [
            ['product_id' => null,     'description' => 'No product', 'qty' => '1',   'uom' => 'pcs'],
            ['product_id' => 'prod-2', 'description' => 'Zero qty',   'qty' => '0',   'uom' => 'pcs'],
            ['product_id' => 'prod-3', 'description' => 'Neg qty',    'qty' => '-1',  'uom' => 'pcs'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: creates delivery order
    // -------------------------------------------------------------------------

    public function test_creates_delivery_order_for_valid_line(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return $data['tenant_id'] === 'tenant-1'
                    && $data['scheduled_date'] === '2026-03-01'
                    && count($data['lines']) === 1
                    && $data['lines'][0]['product_id'] === 'prod-1'
                    && $data['lines'][0]['quantity']   === '2.00000000'
                    && $data['lines'][0]['unit']        === 'pcs';
            })
            ->andReturn((object) ['id' => 'do-uuid-1']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Widget', 'qty' => '2.00000000', 'uom' => 'pcs'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_creates_delivery_order_with_multiple_lines(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return count($data['lines']) === 2
                    && $data['lines'][0]['product_id'] === 'prod-1'
                    && $data['lines'][1]['product_id'] === 'prod-2';
            })
            ->andReturn((object) ['id' => 'do-uuid-2']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Item A', 'qty' => '3', 'uom' => 'pcs'],
            ['product_id' => 'prod-2', 'description' => 'Item B', 'qty' => '5', 'uom' => 'box'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_filters_invalid_lines_and_creates_with_valid_only(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                // Only the valid line should be included
                return count($data['lines']) === 1
                    && $data['lines'][0]['product_id'] === 'prod-valid';
            })
            ->andReturn((object) ['id' => 'do-uuid-3']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => null,         'description' => 'No product', 'qty' => '1', 'uom' => 'pcs'],
            ['product_id' => 'prod-valid', 'description' => 'Valid',       'qty' => '2', 'uom' => 'pcs'],
            ['product_id' => 'prod-zero',  'description' => 'Zero qty',   'qty' => '0', 'uom' => 'pcs'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_maps_description_to_product_name(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return $data['lines'][0]['product_name'] === 'Blue Widget';
            })
            ->andReturn((object) ['id' => 'do-uuid-4']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Blue Widget', 'qty' => '1', 'uom' => 'pcs'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_includes_notes_referencing_sales_order_id(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->withArgs(function (array $data) {
                return str_contains($data['notes'] ?? '', 'so-uuid-99');
            })
            ->andReturn((object) ['id' => 'do-uuid-5']);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(orderId: 'so-uuid-99', lines: [
            ['product_id' => 'prod-1', 'description' => 'Item', 'qty' => '1', 'uom' => 'pcs'],
        ]);

        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: delivery creation failure does not propagate
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_create_delivery_throws(): void
    {
        $useCase = Mockery::mock(CreateDeliveryOrderUseCase::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('Logistics service unavailable'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $event = $this->makeEvent(lines: [
            ['product_id' => 'prod-1', 'description' => 'Item', 'qty' => '1', 'uom' => 'pcs'],
        ]);

        // Must not throw — graceful degradation
        (new HandleSalesOrderConfirmedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }
}
