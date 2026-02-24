<?php

namespace Tests\Unit\POS;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\POS\Application\UseCases\PlaceOrderUseCase;
use Modules\POS\Domain\Contracts\PosOrderPaymentRepositoryInterface;
use Modules\POS\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\POS\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\POS\Domain\Events\PosOrderPlaced;
use Modules\POS\Domain\Events\SplitPaymentProcessed;
use PHPUnit\Framework\TestCase;

class SplitPaymentUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function openSession(): object
    {
        return (object) [
            'id'          => 'session-uuid-1',
            'status'      => 'open',
            'total_sales' => '0.00000000',
            'order_count' => 0,
        ];
    }

    private function makeLine(string $unitPrice, string $quantity): array
    {
        return [
            'product_id'   => 'prod-uuid-1',
            'product_name' => 'Widget',
            'unit_price'   => $unitPrice,
            'quantity'     => $quantity,
            'discount'     => '0',
            'tax_rate'     => '0',
        ];
    }

    private function makeUseCase(
        object $session,
        ?object $order = null,
        bool $withPaymentRepo = true
    ): PlaceOrderUseCase {
        $sessionRepo = Mockery::mock(PosSessionRepositoryInterface::class);
        $sessionRepo->shouldReceive('findById')->andReturn($session);
        $sessionRepo->shouldReceive('update')->andReturn($session);

        $orderRepo = Mockery::mock(PosOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('nextNumber')->andReturn('POS-000001');
        $orderRepo->shouldReceive('create')->andReturn(
            $order ?? (object) ['id' => 'order-uuid-1', 'status' => 'paid', 'total' => '0']
        );

        $paymentRepo = null;
        if ($withPaymentRepo) {
            $paymentRepo = Mockery::mock(PosOrderPaymentRepositoryInterface::class);
            $paymentRepo->shouldReceive('create')->andReturn((object) ['id' => 'pay-uuid-1']);
        }

        return new PlaceOrderUseCase($orderRepo, $sessionRepo, null, $paymentRepo);
    }

    // ---------------------------------------------------------------
    // Tests
    // ---------------------------------------------------------------

    public function test_throws_when_split_payment_sum_does_not_equal_total(): void
    {
        $useCase = $this->makeUseCase($this->openSession());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/do not equal order total/i');

        $useCase->execute([
            'session_id' => 'session-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
            'lines'      => [$this->makeLine('100', '1')], // total = 100
            'payments'   => [
                ['payment_method' => 'cash', 'amount' => '60.00'],  // sum = 60 ≠ 100
            ],
        ]);
    }

    public function test_throws_when_split_payment_amount_is_zero(): void
    {
        $useCase = $this->makeUseCase($this->openSession());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/greater than zero/i');

        $useCase->execute([
            'session_id' => 'session-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
            'lines'      => [$this->makeLine('100', '1')],
            'payments'   => [
                ['payment_method' => 'cash',   'amount' => '0.00'],
                ['payment_method' => 'card',   'amount' => '100.00'],
            ],
        ]);
    }

    public function test_throws_when_insufficient_cash_in_split_payment(): void
    {
        $useCase = $this->makeUseCase($this->openSession());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/insufficient cash/i');

        $useCase->execute([
            'session_id'   => 'session-uuid-1',
            'tenant_id'    => 'tenant-uuid-1',
            'cash_tendered'=> '30.00', // cash portion is 50, tendered 30 < 50
            'lines'        => [$this->makeLine('100', '1')], // total = 100
            'payments'     => [
                ['payment_method' => 'cash', 'amount' => '50.00'],
                ['payment_method' => 'card', 'amount' => '50.00'],
            ],
        ]);
    }

    public function test_successful_split_payment_cash_and_card(): void
    {
        $order = (object) ['id' => 'order-uuid-1', 'status' => 'paid', 'total' => '100.00000000'];
        $useCase = $this->makeUseCase($this->openSession(), $order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->twice() // SplitPaymentProcessed + PosOrderPlaced
            ->withAnyArgs();

        $result = $useCase->execute([
            'session_id' => 'session-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
            'created_by' => 'user-uuid-1',
            'lines'      => [$this->makeLine('100', '1')], // total = 100
            'payments'   => [
                ['payment_method' => 'cash', 'amount' => '50.00000000'],
                ['payment_method' => 'card', 'amount' => '50.00000000'],
            ],
        ]);

        $this->assertSame('paid', $result->status);
    }

    public function test_split_payment_dispatches_split_payment_processed_event(): void
    {
        $order = (object) ['id' => 'order-uuid-1', 'status' => 'paid', 'total' => '200.00000000'];
        $useCase = $this->makeUseCase($this->openSession(), $order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) =>
                $event instanceof SplitPaymentProcessed &&
                $event->orderId === 'order-uuid-1' &&
                $event->paymentCount === 2
            );
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PosOrderPlaced);

        $result = $useCase->execute([
            'session_id' => 'session-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
            'created_by' => 'user-uuid-1',
            'lines'      => [$this->makeLine('200', '1')], // total = 200
            'payments'   => [
                ['payment_method' => 'cash',          'amount' => '100.00000000'],
                ['payment_method' => 'digital_wallet', 'amount' => '100.00000000'],
            ],
        ]);

        $this->assertSame('paid', $result->status);
    }

    public function test_single_payment_in_payments_array_does_not_dispatch_split_event(): void
    {
        $order = (object) ['id' => 'order-uuid-2', 'status' => 'paid', 'total' => '75.00000000'];
        $useCase = $this->makeUseCase($this->openSession(), $order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $splitEventDispatched = false;
        Event::shouldReceive('dispatch')
            ->withAnyArgs()
            ->andReturnUsing(function ($event) use (&$splitEventDispatched) {
                if ($event instanceof SplitPaymentProcessed) {
                    $splitEventDispatched = true;
                }
            });

        $useCase->execute([
            'session_id' => 'session-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
            'created_by' => 'user-uuid-1',
            'lines'      => [$this->makeLine('75', '1')],
            'payments'   => [
                ['payment_method' => 'card', 'amount' => '75.00000000'],
            ],
        ]);

        $this->assertFalse($splitEventDispatched, 'SplitPaymentProcessed should NOT fire for a single payment.');
    }

    public function test_legacy_single_payment_method_still_works(): void
    {
        $order = (object) ['id' => 'order-uuid-3', 'status' => 'paid', 'total' => '50.00000000'];
        $useCase = $this->makeUseCase($this->openSession(), $order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PosOrderPlaced);

        $result = $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 'tenant-uuid-1',
            'created_by'     => 'user-uuid-1',
            'payment_method' => 'card',
            'lines'          => [$this->makeLine('50', '1')],
        ]);

        $this->assertSame('paid', $result->status);
    }

    public function test_split_payment_without_payment_repo_still_places_order(): void
    {
        // When paymentRepo is null the use case should still work (no payment records stored)
        $order = (object) ['id' => 'order-uuid-4', 'status' => 'paid', 'total' => '80.00000000'];
        $useCase = $this->makeUseCase($this->openSession(), $order, withPaymentRepo: false);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof PosOrderPlaced);

        $result = $useCase->execute([
            'session_id' => 'session-uuid-1',
            'tenant_id'  => 'tenant-uuid-1',
            'created_by' => 'user-uuid-1',
            'lines'      => [$this->makeLine('80', '1')],
            'payments'   => [
                ['payment_method' => 'cash', 'amount' => '50.00000000'],
                ['payment_method' => 'card', 'amount' => '30.00000000'],
            ],
        ]);

        $this->assertSame('paid', $result->status);
    }

    public function test_split_payment_cash_change_calculated_from_cash_portion_only(): void
    {
        // Total = 100; cash portion = 60 (tendered 70 → change = 10); card = 40
        $order = (object) ['id' => 'order-uuid-5', 'status' => 'paid', 'total' => '100.00000000'];
        $useCase = $this->makeUseCase($this->openSession(), $order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->withAnyArgs();

        // Should not throw (tendered 70 ≥ cash portion 60)
        $result = $useCase->execute([
            'session_id'    => 'session-uuid-1',
            'tenant_id'     => 'tenant-uuid-1',
            'created_by'    => 'user-uuid-1',
            'cash_tendered' => '70.00000000',
            'lines'         => [$this->makeLine('100', '1')],
            'payments'      => [
                ['payment_method' => 'cash', 'amount' => '60.00000000'],
                ['payment_method' => 'card', 'amount' => '40.00000000'],
            ],
        ]);

        $this->assertSame('paid', $result->status);
    }
}
