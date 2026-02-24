<?php

namespace Tests\Unit\POS;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\POS\Application\UseCases\PlaceOrderUseCase;
use Modules\POS\Domain\Contracts\PosDiscountRepositoryInterface;
use Modules\POS\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\POS\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\POS\Domain\Events\DiscountCodeApplied;
use Modules\POS\Domain\Events\PosOrderPlaced;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PlaceOrderUseCase — discount code application branch.
 *
 * Covers:
 *  - Discount code not found guard
 *  - Inactive discount code guard
 *  - Expired discount code guard
 *  - Usage-limit reached guard
 *  - Percentage discount correctly reduces total (BCMath)
 *  - Fixed-amount discount correctly reduces total (BCMath)
 *  - Fixed-amount capped at order total (never negative)
 *  - Successful order: incrementUsage called + DiscountCodeApplied + PosOrderPlaced events dispatched
 */
class PlaceOrderWithDiscountUseCaseTest extends TestCase
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

    private function makeLine(string $unitPrice, string $quantity): array
    {
        return [
            'product_id'   => 'prod-1',
            'product_name' => 'Widget',
            'unit_price'   => $unitPrice,
            'quantity'     => $quantity,
            'discount'     => '0',
            'tax_rate'     => '0',
        ];
    }

    private function makeDiscount(array $overrides = []): object
    {
        return (object) array_merge([
            'id'          => 'disc-uuid-1',
            'code'        => 'SUMMER10',
            'name'        => 'Summer Sale',
            'type'        => 'percentage',
            'value'       => '10.00000000',
            'usage_limit' => null,
            'times_used'  => 0,
            'expires_at'  => null,
            'is_active'   => true,
        ], $overrides);
    }

    private function makeUseCase(
        object $session,
        ?object $discountRecord,
    ): PlaceOrderUseCase {
        $sessionRepo = Mockery::mock(PosSessionRepositoryInterface::class);
        $sessionRepo->shouldReceive('findById')->andReturn($session);
        $sessionRepo->shouldReceive('update')->andReturn($session);

        $orderRepo = Mockery::mock(PosOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('nextNumber')->andReturn('POS-000001');
        $orderRepo->shouldReceive('create')
            ->andReturnUsing(function ($data) {
                return (object) array_merge(['id' => 'order-uuid-1', 'status' => 'paid'], $data);
            });

        $discountRepo = Mockery::mock(PosDiscountRepositoryInterface::class);
        $discountRepo->shouldReceive('findByCode')->andReturn($discountRecord);
        if ($discountRecord) {
            $discountRepo->shouldReceive('incrementUsage')->andReturn(null);
        }

        return new PlaceOrderUseCase($orderRepo, $sessionRepo, $discountRepo);
    }

    public function test_throws_when_discount_code_not_found(): void
    {
        $useCase = $this->makeUseCase($this->openSession(), null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Discount code not found.');

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 't-1',
            'payment_method' => 'card',
            'discount_code'  => 'NOSUCHCODE',
            'lines'          => [$this->makeLine('100', '1')],
        ]);
    }

    public function test_throws_when_discount_code_is_inactive(): void
    {
        $useCase = $this->makeUseCase($this->openSession(), $this->makeDiscount(['is_active' => false]));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Discount code is inactive.');

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 't-1',
            'payment_method' => 'card',
            'discount_code'  => 'SUMMER10',
            'lines'          => [$this->makeLine('100', '1')],
        ]);
    }

    public function test_throws_when_discount_code_has_expired(): void
    {
        $past = date('Y-m-d H:i:s', strtotime('-1 day'));
        $useCase = $this->makeUseCase($this->openSession(), $this->makeDiscount(['expires_at' => $past]));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Discount code has expired.');

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 't-1',
            'payment_method' => 'card',
            'discount_code'  => 'SUMMER10',
            'lines'          => [$this->makeLine('100', '1')],
        ]);
    }

    public function test_throws_when_usage_limit_reached(): void
    {
        $useCase = $this->makeUseCase(
            $this->openSession(),
            $this->makeDiscount(['usage_limit' => 5, 'times_used' => 5])
        );

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Discount code usage limit has been reached.');

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 't-1',
            'payment_method' => 'card',
            'discount_code'  => 'SUMMER10',
            'lines'          => [$this->makeLine('100', '1')],
        ]);
    }

    public function test_percentage_discount_reduces_total_correctly(): void
    {
        // subtotal=100, no tax, 10% discount → discount_amount=10, total=90
        $sessionRepo = Mockery::mock(PosSessionRepositoryInterface::class);
        $sessionRepo->shouldReceive('findById')->andReturn($this->openSession());
        $sessionRepo->shouldReceive('update')->andReturn($this->openSession());

        $capture = new \stdClass();
        $orderRepo = Mockery::mock(PosOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('nextNumber')->andReturn('POS-000001');
        $orderRepo->shouldReceive('create')
            ->andReturnUsing(function ($data) use ($capture) {
                $capture->data = $data;
                return (object) array_merge(['id' => 'order-uuid-1', 'status' => 'paid'], $data);
            });

        $discountRepo = Mockery::mock(PosDiscountRepositoryInterface::class);
        $discountRepo->shouldReceive('findByCode')->andReturn(
            $this->makeDiscount(['type' => 'percentage', 'value' => '10.00000000'])
        );
        $discountRepo->shouldReceive('incrementUsage')->andReturn(null);

        $useCase = new PlaceOrderUseCase($orderRepo, $sessionRepo, $discountRepo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->twice(); // DiscountCodeApplied + PosOrderPlaced

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 't-1',
            'created_by'     => 'user-uuid-1',
            'payment_method' => 'card',
            'discount_code'  => 'SUMMER10',
            'lines'          => [$this->makeLine('100', '1')],
        ]);

        $this->assertSame('10.00000000', $capture->data['discount_amount']);
        $this->assertSame('90.00000000', $capture->data['total']);
        $this->assertSame('disc-uuid-1', $capture->data['discount_code_id']);
    }

    public function test_fixed_amount_discount_reduces_total_correctly(): void
    {
        // subtotal=100, no tax, fixed $15 off → discount_amount=15, total=85
        $sessionRepo = Mockery::mock(PosSessionRepositoryInterface::class);
        $sessionRepo->shouldReceive('findById')->andReturn($this->openSession());
        $sessionRepo->shouldReceive('update')->andReturn($this->openSession());

        $capture = new \stdClass();
        $orderRepo = Mockery::mock(PosOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('nextNumber')->andReturn('POS-000001');
        $orderRepo->shouldReceive('create')
            ->andReturnUsing(function ($data) use ($capture) {
                $capture->data = $data;
                return (object) array_merge(['id' => 'order-uuid-1', 'status' => 'paid'], $data);
            });

        $discountRepo = Mockery::mock(PosDiscountRepositoryInterface::class);
        $discountRepo->shouldReceive('findByCode')->andReturn(
            $this->makeDiscount(['type' => 'fixed_amount', 'value' => '15.00000000'])
        );
        $discountRepo->shouldReceive('incrementUsage')->andReturn(null);

        $useCase = new PlaceOrderUseCase($orderRepo, $sessionRepo, $discountRepo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->twice();

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 't-1',
            'created_by'     => 'user-uuid-1',
            'payment_method' => 'card',
            'discount_code'  => 'FLATOFF',
            'lines'          => [$this->makeLine('100', '1')],
        ]);

        $this->assertSame('15.00000000', $capture->data['discount_amount']);
        $this->assertSame('85.00000000', $capture->data['total']);
    }

    public function test_fixed_amount_capped_at_order_total(): void
    {
        // subtotal=50, fixed $200 off → discount_amount capped at 50, total=0
        $sessionRepo = Mockery::mock(PosSessionRepositoryInterface::class);
        $sessionRepo->shouldReceive('findById')->andReturn($this->openSession());
        $sessionRepo->shouldReceive('update')->andReturn($this->openSession());

        $capture = new \stdClass();
        $orderRepo = Mockery::mock(PosOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('nextNumber')->andReturn('POS-000001');
        $orderRepo->shouldReceive('create')
            ->andReturnUsing(function ($data) use ($capture) {
                $capture->data = $data;
                return (object) array_merge(['id' => 'order-uuid-1', 'status' => 'paid'], $data);
            });

        $discountRepo = Mockery::mock(PosDiscountRepositoryInterface::class);
        $discountRepo->shouldReceive('findByCode')->andReturn(
            $this->makeDiscount(['type' => 'fixed_amount', 'value' => '200.00000000'])
        );
        $discountRepo->shouldReceive('incrementUsage')->andReturn(null);

        $useCase = new PlaceOrderUseCase($orderRepo, $sessionRepo, $discountRepo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->twice();

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 't-1',
            'created_by'     => 'user-uuid-1',
            'payment_method' => 'card',
            'discount_code'  => 'BIGOFF',
            'lines'          => [$this->makeLine('50', '1')],
        ]);

        // discount_amount is capped at 50 (the full total), total is 0
        $this->assertSame('50.00000000', $capture->data['discount_amount']);
        $this->assertSame('0.00000000', $capture->data['total']);
    }

    public function test_percentage_discount_dispatches_discount_code_applied_event(): void
    {
        $useCase = $this->makeUseCase(
            $this->openSession(),
            $this->makeDiscount()
        );

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $dispatchedEvents = [];
        Event::shouldReceive('dispatch')
            ->andReturnUsing(function ($event) use (&$dispatchedEvents) {
                $dispatchedEvents[] = $event;
            });

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 't-1',
            'created_by'     => 'user-uuid-1',
            'payment_method' => 'card',
            'discount_code'  => 'SUMMER10',
            'lines'          => [$this->makeLine('100', '1')],
        ]);

        $hasDiscountEvent = collect($dispatchedEvents)->contains(
            fn ($e) => $e instanceof DiscountCodeApplied
        );
        $hasPosOrderEvent = collect($dispatchedEvents)->contains(
            fn ($e) => $e instanceof PosOrderPlaced
        );

        $this->assertTrue($hasDiscountEvent, 'DiscountCodeApplied event was not dispatched.');
        $this->assertTrue($hasPosOrderEvent, 'PosOrderPlaced event was not dispatched.');
    }

    public function test_order_without_discount_code_has_zero_discount_amount(): void
    {
        $sessionRepo = Mockery::mock(PosSessionRepositoryInterface::class);
        $sessionRepo->shouldReceive('findById')->andReturn($this->openSession());
        $sessionRepo->shouldReceive('update')->andReturn($this->openSession());

        $capturedData = [];
        $orderRepo = Mockery::mock(PosOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('nextNumber')->andReturn('POS-000001');
        $orderRepo->shouldReceive('create')
            ->andReturnUsing(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return (object) array_merge(['id' => 'order-uuid-1', 'status' => 'paid'], $data);
            });

        // No discount repo provided
        $useCase = new PlaceOrderUseCase($orderRepo, $sessionRepo);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once()->with(Mockery::type(PosOrderPlaced::class));

        $useCase->execute([
            'session_id'     => 'session-uuid-1',
            'tenant_id'      => 't-1',
            'created_by'     => 'user-uuid-1',
            'payment_method' => 'card',
            'lines'          => [$this->makeLine('100', '1')],
        ]);

        $this->assertSame('0.00000000', $capturedData['discount_amount']);
        $this->assertNull($capturedData['discount_code_id']);
        $this->assertSame('100.00000000', $capturedData['total']);
    }
}
