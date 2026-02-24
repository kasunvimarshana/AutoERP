<?php

namespace Tests\Unit\SubscriptionBilling;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\SubscriptionBilling\Application\UseCases\CancelSubscriptionUseCase;
use Modules\SubscriptionBilling\Application\UseCases\CreateSubscriptionUseCase;
use Modules\SubscriptionBilling\Application\UseCases\RenewSubscriptionUseCase;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionCancelled;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionCreated;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionRenewed;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Subscription Billing use cases.
 *
 * Covers plan validation guards, subscription lifecycle transitions,
 * trial period assignment, and domain event dispatch.
 */
class SubscriptionBillingUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePlan(bool $isActive = true, int $trialDays = 0, string $cycle = 'monthly'): object
    {
        return (object) [
            'id'            => 'plan-uuid-1',
            'is_active'     => $isActive,
            'billing_cycle' => $cycle,
            'price'         => '29.99000000',
            'trial_days'    => $trialDays,
        ];
    }

    private function makeSubscription(string $status = 'active'): object
    {
        return (object) [
            'id'      => 'sub-uuid-1',
            'plan_id' => 'plan-uuid-1',
            'status'  => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateSubscriptionUseCase
    // -------------------------------------------------------------------------

    public function test_throws_when_plan_not_found(): void
    {
        $planRepo = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
        $planRepo->shouldReceive('findById')->andReturn(null);

        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateSubscriptionUseCase($planRepo, $subRepo);

        $this->expectException(ModelNotFoundException::class);

        $useCase->execute([
            'plan_id'         => 'missing-plan',
            'subscriber_type' => 'tenant',
            'subscriber_id'   => 'tenant-uuid-1',
            'tenant_id'       => 'tenant-uuid-1',
        ]);
    }

    public function test_throws_when_plan_is_not_active(): void
    {
        $planRepo = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
        $planRepo->shouldReceive('findById')->andReturn($this->makePlan(false));

        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CreateSubscriptionUseCase($planRepo, $subRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not active/i');

        $useCase->execute([
            'plan_id'         => 'plan-uuid-1',
            'subscriber_type' => 'tenant',
            'subscriber_id'   => 'tenant-uuid-1',
            'tenant_id'       => 'tenant-uuid-1',
        ]);
    }

    public function test_creates_active_subscription_with_no_trial(): void
    {
        $plan        = $this->makePlan(true, 0);
        $created     = $this->makeSubscription('active');

        $planRepo = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
        $planRepo->shouldReceive('findById')->andReturn($plan);

        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($d) => $d['status'] === 'active' && $d['trial_ends_at'] === null)
            ->andReturn($created);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof SubscriptionCreated);

        $useCase = new CreateSubscriptionUseCase($planRepo, $subRepo);

        $result = $useCase->execute([
            'plan_id'         => 'plan-uuid-1',
            'subscriber_type' => 'tenant',
            'subscriber_id'   => 'tenant-uuid-1',
            'tenant_id'       => 'tenant-uuid-1',
        ]);

        $this->assertSame('active', $result->status);
    }

    public function test_creates_trial_subscription_when_plan_has_trial_days(): void
    {
        $plan    = $this->makePlan(true, 14);
        $created = $this->makeSubscription('trial');

        $planRepo = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
        $planRepo->shouldReceive('findById')->andReturn($plan);

        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($d) => $d['status'] === 'trial' && $d['trial_ends_at'] !== null)
            ->andReturn($created);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof SubscriptionCreated);

        $useCase = new CreateSubscriptionUseCase($planRepo, $subRepo);

        $result = $useCase->execute([
            'plan_id'         => 'plan-uuid-1',
            'subscriber_type' => 'tenant',
            'subscriber_id'   => 'tenant-uuid-1',
            'tenant_id'       => 'tenant-uuid-1',
        ]);

        $this->assertSame('trial', $result->status);
    }

    // -------------------------------------------------------------------------
    // RenewSubscriptionUseCase
    // -------------------------------------------------------------------------

    public function test_renew_throws_when_subscription_not_found(): void
    {
        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subRepo->shouldReceive('findById')->andReturn(null);

        $planRepo = Mockery::mock(SubscriptionPlanRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new RenewSubscriptionUseCase($subRepo, $planRepo);

        $this->expectException(ModelNotFoundException::class);

        $useCase->execute('missing-sub');
    }

    public function test_renew_throws_when_subscription_is_cancelled(): void
    {
        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subRepo->shouldReceive('findById')->andReturn($this->makeSubscription('cancelled'));

        $planRepo = Mockery::mock(SubscriptionPlanRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new RenewSubscriptionUseCase($subRepo, $planRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/active or trial/i');

        $useCase->execute('sub-uuid-1');
    }

    public function test_renew_transitions_to_active_and_dispatches_event(): void
    {
        $subscription = $this->makeSubscription('trial');
        $renewed      = (object) array_merge((array) $subscription, ['status' => 'active', 'trial_ends_at' => null]);

        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subRepo->shouldReceive('findById')->andReturn($subscription);
        $subRepo->shouldReceive('update')
            ->withArgs(fn ($id, $d) => $d['status'] === 'active')
            ->once()
            ->andReturn($renewed);

        $planRepo = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
        $planRepo->shouldReceive('findById')->andReturn($this->makePlan());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof SubscriptionRenewed);

        $useCase = new RenewSubscriptionUseCase($subRepo, $planRepo);
        $result  = $useCase->execute('sub-uuid-1');

        $this->assertSame('active', $result->status);
    }

    // -------------------------------------------------------------------------
    // CancelSubscriptionUseCase
    // -------------------------------------------------------------------------

    public function test_cancel_throws_when_subscription_not_found(): void
    {
        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CancelSubscriptionUseCase($subRepo);

        $this->expectException(ModelNotFoundException::class);

        $useCase->execute('missing-sub');
    }

    public function test_cancel_throws_when_already_cancelled(): void
    {
        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subRepo->shouldReceive('findById')->andReturn($this->makeSubscription('cancelled'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CancelSubscriptionUseCase($subRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already cancelled/i');

        $useCase->execute('sub-uuid-1');
    }

    public function test_cancel_sets_status_and_dispatches_event(): void
    {
        $subscription = $this->makeSubscription('active');
        $cancelled    = (object) array_merge((array) $subscription, ['status' => 'cancelled']);

        $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
        $subRepo->shouldReceive('findById')->andReturn($subscription);
        $subRepo->shouldReceive('update')
            ->withArgs(fn ($id, $d) => $d['status'] === 'cancelled')
            ->once()
            ->andReturn($cancelled);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof SubscriptionCancelled);

        $useCase = new CancelSubscriptionUseCase($subRepo);
        $result  = $useCase->execute('sub-uuid-1');

        $this->assertSame('cancelled', $result->status);
    }
}
