<?php

namespace Tests\Unit\POS;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\POS\Application\Listeners\HandlePosOrderPlacedLoyaltyListener;
use Modules\POS\Application\UseCases\AccrueLoyaltyPointsUseCase;
use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;
use Modules\POS\Domain\Events\LoyaltyPointsAccrued;
use Modules\POS\Domain\Events\PosOrderPlaced;
use PHPUnit\Framework\TestCase;

class PosLoyaltyAutoAccrualListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeEvent(array $overrides = []): PosOrderPlaced
    {
        return new PosOrderPlaced(
            orderId:     array_key_exists('orderId', $overrides)     ? $overrides['orderId']     : 'order-1',
            tenantId:    array_key_exists('tenantId', $overrides)    ? $overrides['tenantId']    : 't-1',
            lines:       array_key_exists('lines', $overrides)       ? $overrides['lines']       : [],
            customerId:  array_key_exists('customerId', $overrides)  ? $overrides['customerId']  : 'cust-1',
            totalAmount: array_key_exists('totalAmount', $overrides) ? $overrides['totalAmount'] : '100.00000000',
        );
    }

    private function makeProgram(): object
    {
        return (object) [
            'id'                       => 'prog-1',
            'tenant_id'                => 't-1',
            'is_active'                => true,
            'points_per_currency_unit' => '1',
            'redemption_rate'          => '100',
        ];
    }

    // -------------------------------------------------------------------------
    // Skip conditions
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $repo        = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $accrueCase  = Mockery::mock(AccrueLoyaltyPointsUseCase::class);

        $repo->shouldReceive('findActiveByTenant')->never();
        $accrueCase->shouldNotReceive('execute');

        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($this->makeEvent(['tenantId' => '']));

        $this->addToAssertionCount(1);
    }

    public function test_skips_when_customer_id_is_null(): void
    {
        $repo        = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $accrueCase  = Mockery::mock(AccrueLoyaltyPointsUseCase::class);

        $repo->shouldReceive('findActiveByTenant')->never();
        $accrueCase->shouldNotReceive('execute');

        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($this->makeEvent(['customerId' => null]));

        $this->addToAssertionCount(1);
    }

    public function test_skips_when_total_amount_is_zero(): void
    {
        $repo        = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $accrueCase  = Mockery::mock(AccrueLoyaltyPointsUseCase::class);

        $repo->shouldReceive('findActiveByTenant')->never();
        $accrueCase->shouldNotReceive('execute');

        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($this->makeEvent(['totalAmount' => '0']));

        $this->addToAssertionCount(1);
    }

    public function test_skips_when_total_amount_is_negative(): void
    {
        $repo        = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $accrueCase  = Mockery::mock(AccrueLoyaltyPointsUseCase::class);

        $repo->shouldReceive('findActiveByTenant')->never();
        $accrueCase->shouldNotReceive('execute');

        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($this->makeEvent(['totalAmount' => '-5']));

        $this->addToAssertionCount(1);
    }

    public function test_skips_when_no_active_loyalty_program(): void
    {
        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findActiveByTenant')->with('t-1')->andReturn(null);

        $accrueCase = Mockery::mock(AccrueLoyaltyPointsUseCase::class);
        $accrueCase->shouldNotReceive('execute');

        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Successful accrual
    // -------------------------------------------------------------------------

    public function test_accrues_points_for_customer_with_active_program(): void
    {
        $program = $this->makeProgram();

        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findActiveByTenant')->with('t-1')->andReturn($program);

        $accrueCase = Mockery::mock(AccrueLoyaltyPointsUseCase::class);
        $accrueCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['tenant_id']    === 't-1'     &&
                $d['program_id']   === 'prog-1'  &&
                $d['customer_id']  === 'cust-1'  &&
                $d['order_amount'] === '100.00000000' &&
                $d['reference']    === 'order-1'
            ))
            ->andReturn((object) ['id' => 'card-1', 'points_balance' => '100']);

        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_passes_order_id_as_reference(): void
    {
        $program = $this->makeProgram();

        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findActiveByTenant')->with('t-1')->andReturn($program);

        $accrueCase = Mockery::mock(AccrueLoyaltyPointsUseCase::class);
        $accrueCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['reference'] === 'order-99'))
            ->andReturn((object) ['id' => 'card-1', 'points_balance' => '50']);

        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($this->makeEvent(['orderId' => 'order-99']));

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Backwards-compatibility: old PosOrderPlaced without customerId
    // -------------------------------------------------------------------------

    public function test_backwards_compatible_event_without_customer_id_is_skipped(): void
    {
        $repo       = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $accrueCase = Mockery::mock(AccrueLoyaltyPointsUseCase::class);

        $repo->shouldReceive('findActiveByTenant')->never();
        $accrueCase->shouldNotReceive('execute');

        // Simulate an old-style event dispatched without customerId (defaults to null)
        $event    = new PosOrderPlaced('order-old', 't-1');
        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_accrue_throws_domain_exception(): void
    {
        $program = $this->makeProgram();

        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findActiveByTenant')->with('t-1')->andReturn($program);

        $accrueCase = Mockery::mock(AccrueLoyaltyPointsUseCase::class);
        $accrueCase->shouldReceive('execute')->once()->andThrow(new DomainException('too small'));

        // Must not throw
        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    public function test_graceful_degradation_when_accrue_throws_runtime_exception(): void
    {
        $program = $this->makeProgram();

        $repo = Mockery::mock(LoyaltyProgramRepositoryInterface::class);
        $repo->shouldReceive('findActiveByTenant')->with('t-1')->andReturn($program);

        $accrueCase = Mockery::mock(AccrueLoyaltyPointsUseCase::class);
        $accrueCase->shouldReceive('execute')->once()->andThrow(new \RuntimeException('DB error'));

        $listener = new HandlePosOrderPlacedLoyaltyListener($repo, $accrueCase);
        $listener->handle($this->makeEvent());

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // PosOrderPlaced event enrichment
    // -------------------------------------------------------------------------

    public function test_pos_order_placed_event_carries_customer_id_and_total(): void
    {
        $event = new PosOrderPlaced(
            orderId:     'ord-1',
            tenantId:    't-1',
            lines:       [],
            customerId:  'cust-42',
            totalAmount: '250.00',
        );

        $this->assertSame('cust-42', $event->customerId);
        $this->assertSame('250.00', $event->totalAmount);
    }

    public function test_pos_order_placed_event_defaults_customer_id_null_and_total_zero(): void
    {
        $event = new PosOrderPlaced('ord-2', 't-1');

        $this->assertNull($event->customerId);
        $this->assertSame('0', $event->totalAmount);
    }
}
