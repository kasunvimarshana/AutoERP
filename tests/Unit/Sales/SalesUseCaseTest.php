<?php

namespace Tests\Unit\Sales;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Sales\Application\Services\OrderTotalsCalculator;
use Modules\Sales\Application\UseCases\CancelOrderUseCase;
use Modules\Sales\Application\UseCases\ConfirmOrderUseCase;
use Modules\Sales\Application\UseCases\ConvertQuotationToOrderUseCase;
use Modules\Sales\Application\UseCases\CreateQuotationUseCase;
use Modules\Sales\Domain\Contracts\QuotationRepositoryInterface;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\Events\OrderCancelled;
use Modules\Sales\Domain\Events\OrderConfirmed;
use Modules\Sales\Domain\Events\QuotationCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Sales module use cases.
 *
 * Covers quotation creation with BCMath totals, quotation-to-order conversion,
 * order confirmation lifecycle, and order cancellation guards.
 */
class SalesUseCaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // CreateQuotationUseCase calls auth()->user() to resolve tenant_id.
        // Register a null-returning mock so app(AuthFactory::class) resolves
        // without a full Laravel bootstrap.
        $authMock = Mockery::mock(AuthFactory::class);
        $authMock->shouldReceive('user')->andReturn(null)->byDefault();
        $authMock->shouldReceive('id')->andReturn(null)->byDefault();
        $authMock->shouldReceive('guard')->andReturn($authMock)->byDefault();
        Container::getInstance()->instance(AuthFactory::class, $authMock);
        Container::getInstance()->instance('auth', $authMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeQuotation(string $status = 'draft'): object
    {
        return (object) [
            'id'          => 'quot-uuid-1',
            'tenant_id'   => 'tenant-uuid-1',
            'number'      => 'Q-2026-000001',
            'customer_id' => 'cust-uuid-1',
            'status'      => $status,
            'subtotal'    => '100.00000000',
            'tax_total'   => '10.00000000',
            'total'       => '110.00000000',
            'currency'    => 'USD',
        ];
    }

    private function makeOrder(string $status = 'confirmed'): object
    {
        return (object) [
            'id'     => 'order-uuid-1',
            'status' => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // OrderTotalsCalculator (BCMath service)
    // -------------------------------------------------------------------------

    public function test_order_totals_calculator_applies_bcmath_discount_and_tax(): void
    {
        $calculator = new OrderTotalsCalculator();

        $result = $calculator->calculate([
            [
                'qty'          => '2',
                'unit_price'   => '50',
                'discount_pct' => '10',
                'tax_rate'     => '20',
            ],
        ]);

        // line_total = (2 * 50) - 10% = 100 - 10 = 90
        // tax        = 90 * 20% = 18
        // subtotal   = 90, tax_total = 18, total = 108
        $this->assertSame('90.00000000', $result['subtotal']);
        $this->assertSame('18.00000000', $result['tax_total']);
        $this->assertSame('108.00000000', $result['total']);
    }

    public function test_order_totals_calculator_handles_zero_discount_and_tax(): void
    {
        $calculator = new OrderTotalsCalculator();

        $result = $calculator->calculate([
            ['qty' => '3', 'unit_price' => '100'],
        ]);

        $this->assertSame('300.00000000', $result['subtotal']);
        $this->assertSame('0.00000000', $result['tax_total']);
        $this->assertSame('300.00000000', $result['total']);
    }

    // -------------------------------------------------------------------------
    // CreateQuotationUseCase
    // -------------------------------------------------------------------------

    public function test_create_quotation_sets_status_draft_and_dispatches_event(): void
    {
        $quotation    = $this->makeQuotation();
        $quotRepo     = Mockery::mock(QuotationRepositoryInterface::class);
        $calculator   = new OrderTotalsCalculator();

        $quotRepo->shouldReceive('nextNumber')->andReturn('Q-2026-000001');
        $quotRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'draft'
                && $data['total'] === '0.00000000')
            ->andReturn($quotation);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof QuotationCreated
                && $event->quotationId === 'quot-uuid-1');

        $useCase = new CreateQuotationUseCase($quotRepo, $calculator);
        $result  = $useCase->execute([
            'tenant_id'   => 'tenant-uuid-1',
            'customer_id' => 'cust-uuid-1',
        ]);

        $this->assertSame('draft', $result->status);
    }

    public function test_create_quotation_calculates_line_totals_with_bcmath(): void
    {
        $quotation  = (object) [
            'id'       => 'quot-uuid-2',
            'status'   => 'draft',
            'subtotal' => '200.00000000',
            'tax_total' => '20.00000000',
            'total'    => '220.00000000',
        ];
        $quotRepo   = Mockery::mock(QuotationRepositoryInterface::class);
        $calculator = new OrderTotalsCalculator();

        $quotRepo->shouldReceive('nextNumber')->andReturn('Q-2026-000002');
        $quotRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['subtotal'] === '200.00000000'
                && $data['tax_total'] === '20.00000000'
                && $data['total'] === '220.00000000')
            ->andReturn($quotation);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreateQuotationUseCase($quotRepo, $calculator);
        $result  = $useCase->execute([
            'tenant_id'   => 'tenant-uuid-1',
            'customer_id' => 'cust-uuid-1',
            'lines'       => [
                ['qty' => '2', 'unit_price' => '100', 'tax_rate' => '10'],
            ],
        ]);

        $this->assertSame('220.00000000', $result->total);
    }

    // -------------------------------------------------------------------------
    // ConvertQuotationToOrderUseCase
    // -------------------------------------------------------------------------

    public function test_convert_quotation_throws_when_not_found(): void
    {
        $quotRepo  = Mockery::mock(QuotationRepositoryInterface::class);
        $orderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);

        $quotRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ConvertQuotationToOrderUseCase($quotRepo, $orderRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_convert_quotation_throws_when_status_not_accepted_or_sent(): void
    {
        $quotRepo  = Mockery::mock(QuotationRepositoryInterface::class);
        $orderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);

        $quotRepo->shouldReceive('findById')->andReturn($this->makeQuotation('draft'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ConvertQuotationToOrderUseCase($quotRepo, $orderRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/accepted or sent/i');

        $useCase->execute('quot-uuid-1');
    }

    public function test_convert_quotation_creates_order_and_dispatches_event(): void
    {
        $quotation = $this->makeQuotation('sent');
        $order     = $this->makeOrder();

        $quotRepo  = Mockery::mock(QuotationRepositoryInterface::class);
        $orderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);

        $quotRepo->shouldReceive('findById')->andReturn($quotation);
        $orderRepo->shouldReceive('nextNumber')->andReturn('SO-2026-000001');
        $orderRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'confirmed'
                && $data['quotation_id'] === 'quot-uuid-1')
            ->andReturn($order);
        $quotRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'accepted');

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof OrderConfirmed
                && $event->orderId === 'order-uuid-1');

        $useCase = new ConvertQuotationToOrderUseCase($quotRepo, $orderRepo);
        $result  = $useCase->execute('quot-uuid-1');

        $this->assertSame('order-uuid-1', $result->id);
    }

    // -------------------------------------------------------------------------
    // ConfirmOrderUseCase
    // -------------------------------------------------------------------------

    public function test_confirm_order_throws_when_not_found(): void
    {
        $orderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ConfirmOrderUseCase($orderRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_confirm_order_throws_when_not_draft(): void
    {
        $orderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->andReturn($this->makeOrder('confirmed'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ConfirmOrderUseCase($orderRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/only draft/i');

        $useCase->execute('order-uuid-1');
    }

    public function test_confirm_order_transitions_and_dispatches_event(): void
    {
        $draft     = $this->makeOrder('draft');
        $confirmed = $this->makeOrder('confirmed');

        $orderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->andReturn($draft);
        $orderRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'confirmed')
            ->andReturn($confirmed);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof OrderConfirmed
                && $event->orderId === 'order-uuid-1');

        $useCase = new ConfirmOrderUseCase($orderRepo);
        $result  = $useCase->execute('order-uuid-1');

        $this->assertSame('confirmed', $result->status);
    }

    // -------------------------------------------------------------------------
    // CancelOrderUseCase
    // -------------------------------------------------------------------------

    public function test_cancel_order_throws_when_not_found(): void
    {
        $orderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CancelOrderUseCase($orderRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_cancel_order_throws_when_already_shipped(): void
    {
        $orderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->andReturn($this->makeOrder('shipped'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CancelOrderUseCase($orderRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/cannot cancel/i');

        $useCase->execute('order-uuid-1');
    }

    public function test_cancel_order_transitions_and_dispatches_event(): void
    {
        $draft     = $this->makeOrder('draft');
        $cancelled = $this->makeOrder('cancelled');

        $orderRepo = Mockery::mock(SalesOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->andReturn($draft);
        $orderRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'cancelled'
                && $data['cancellation_reason'] === 'Customer request')
            ->andReturn($cancelled);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof OrderCancelled
                && $event->orderId === 'order-uuid-1'
                && $event->reason === 'Customer request');

        $useCase = new CancelOrderUseCase($orderRepo);
        $result  = $useCase->execute('order-uuid-1', 'Customer request');

        $this->assertSame('cancelled', $result->status);
    }
}
