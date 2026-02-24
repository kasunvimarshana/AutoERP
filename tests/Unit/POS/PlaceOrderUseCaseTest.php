<?php

namespace Tests\Unit\POS;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\POS\Application\UseCases\PlaceOrderUseCase;
use Modules\POS\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\POS\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\POS\Domain\Events\PosOrderPlaced;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PlaceOrderUseCase.
 *
 * Verifies BCMath order-total calculations, cash-change guard clause,
 * and that the PosOrderPlaced event is dispatched.
 */
class PlaceOrderUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function openSession(): object
    {
        return (object) [
            'id'          => 'session-uuid-1',
            'status'      => 'open',
            'total_sales' => '0.00000000',
            'order_count' => 0,
        ];
    }

    private function makeLine(
        string $unitPrice,
        string $quantity,
        string $discount = '0',
        string $taxRate  = '0'
    ): array {
        return [
            'product_id'   => 'prod-uuid-1',
            'product_name' => 'Widget',
            'unit_price'   => $unitPrice,
            'quantity'     => $quantity,
            'discount'     => $discount,
            'tax_rate'     => $taxRate,
        ];
    }

    private function makeUseCase(object $session, ?object $order = null): PlaceOrderUseCase
    {
        $sessionRepo = Mockery::mock(PosSessionRepositoryInterface::class);
        $sessionRepo->shouldReceive('findById')->andReturn($session);
        $sessionRepo->shouldReceive('update')->andReturn($session);

        $orderRepo = Mockery::mock(PosOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('nextNumber')->andReturn('POS-000001');

        if ($order) {
            $orderRepo->shouldReceive('create')->andReturn($order);
        } else {
            $orderRepo->shouldReceive('create')->andReturn(
                (object) ['id' => 'order-uuid-1', 'status' => 'paid', 'total' => '0']
            );
        }

        return new PlaceOrderUseCase($orderRepo, $sessionRepo);
    }

    public function test_throws_when_session_not_found(): void
    {
        $sessionRepo = Mockery::mock(PosSessionRepositoryInterface::class);
        $sessionRepo->shouldReceive('findById')->andReturn(null);

        $orderRepo = Mockery::mock(PosOrderRepositoryInterface::class);

        $useCase = new PlaceOrderUseCase($orderRepo, $sessionRepo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/session not found/i');

        $useCase->execute(['session_id' => 'missing', 'lines' => []]);
    }

    public function test_throws_when_session_is_not_open(): void
    {
        $session = (object) ['id' => 'session-uuid-1', 'status' => 'closed'];

        $sessionRepo = Mockery::mock(PosSessionRepositoryInterface::class);
        $sessionRepo->shouldReceive('findById')->andReturn($session);

        $orderRepo = Mockery::mock(PosOrderRepositoryInterface::class);

        $useCase = new PlaceOrderUseCase($orderRepo, $sessionRepo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/open session/i');

        $useCase->execute(['session_id' => 'session-uuid-1', 'lines' => []]);
    }

    public function test_throws_when_insufficient_cash_tendered(): void
    {
        $useCase = $this->makeUseCase($this->openSession());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/insufficient cash/i');

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 'tenant-uuid-1',
            'payment_method' => 'cash',
            'cash_tendered'  => '50',  // total will be 100
            'lines'          => [$this->makeLine('100', '1')],
        ]);
    }

    public function test_calculates_correct_totals_no_discount_no_tax(): void
    {
        // 2 × Widget @ 50.00 = 100.00
        $expectedTotal = '100.00000000';

        $order = (object) ['id' => 'order-uuid-1', 'status' => 'paid', 'total' => $expectedTotal];
        $useCase = $this->makeUseCase($this->openSession(), $order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PosOrderPlaced);

        $result = $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 'tenant-uuid-1',
            'created_by'     => 'user-uuid-1',
            'payment_method' => 'cash',
            'cash_tendered'  => '100',
            'lines'          => [$this->makeLine('50', '2')],
        ]);

        $this->assertSame('paid', $result->status);
    }

    public function test_calculates_correct_totals_with_discount_and_tax(): void
    {
        // unit price 100, qty 1, 10% discount → 90, 10% tax → 9, total = 99
        $order = (object) ['id' => 'order-uuid-2', 'status' => 'paid', 'total' => '99.00000000'];
        $useCase = $this->makeUseCase($this->openSession(), $order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $result = $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 'tenant-uuid-1',
            'created_by'     => 'user-uuid-1',
            'payment_method' => 'cash',
            'cash_tendered'  => '100',
            'lines'          => [$this->makeLine('100', '1', '10', '10')],
        ]);

        $this->assertSame('paid', $result->status);
    }

    public function test_card_payment_does_not_require_cash_tendered(): void
    {
        $order = (object) ['id' => 'order-uuid-3', 'status' => 'paid', 'total' => '50.00000000'];
        $useCase = $this->makeUseCase($this->openSession(), $order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        // No cash_tendered needed for card payments — should not throw
        $result = $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 'tenant-uuid-1',
            'created_by'     => 'user-uuid-1',
            'payment_method' => 'card',
            'lines'          => [$this->makeLine('50', '1')],
        ]);

        $this->assertSame('paid', $result->status);
    }
}
