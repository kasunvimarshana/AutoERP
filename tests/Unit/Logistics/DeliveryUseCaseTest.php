<?php

namespace Tests\Unit\Logistics;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Logistics\Application\UseCases\CompleteDeliveryUseCase;
use Modules\Logistics\Application\UseCases\DispatchDeliveryUseCase;
use Modules\Logistics\Domain\Contracts\DeliveryOrderRepositoryInterface;
use Modules\Logistics\Domain\Contracts\TrackingEventRepositoryInterface;
use Modules\Logistics\Domain\Events\DeliveryCompleted;
use Modules\Logistics\Domain\Events\DeliveryDispatched;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Logistics delivery use cases.
 *
 * Verifies status guards, tracking event creation, and domain event dispatch
 * for DispatchDeliveryUseCase and CompleteDeliveryUseCase.
 */
class DeliveryUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeOrder(string $id, string $status): object
    {
        return (object) [
            'id'        => $id,
            'tenant_id' => 'tenant-uuid-1',
            'status'    => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // DispatchDeliveryUseCase
    // -------------------------------------------------------------------------

    public function test_dispatch_throws_when_order_not_found(): void
    {
        $orderRepo   = Mockery::mock(DeliveryOrderRepositoryInterface::class);
        $trackingRepo = Mockery::mock(TrackingEventRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new DispatchDeliveryUseCase($orderRepo, $trackingRepo);

        $this->expectException(ModelNotFoundException::class);

        $useCase->execute('missing-id');
    }

    public function test_dispatch_throws_when_order_not_pending(): void
    {
        $order = $this->makeOrder('do-uuid-1', 'dispatched');

        $orderRepo   = Mockery::mock(DeliveryOrderRepositoryInterface::class);
        $trackingRepo = Mockery::mock(TrackingEventRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn($order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new DispatchDeliveryUseCase($orderRepo, $trackingRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/pending/i');

        $useCase->execute('do-uuid-1');
    }

    public function test_dispatch_transitions_status_and_creates_tracking_event(): void
    {
        $order      = $this->makeOrder('do-uuid-1', 'pending');
        $dispatched = (object) array_merge((array) $order, ['status' => 'dispatched']);

        $orderRepo = Mockery::mock(DeliveryOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->andReturn($order, $dispatched);
        $orderRepo->shouldReceive('update')
            ->with('do-uuid-1', ['status' => 'dispatched'])
            ->once();

        $trackingRepo = Mockery::mock(TrackingEventRepositoryInterface::class);
        $trackingRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['event_type'] === 'picked_up')
            ->andReturn((object) []);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof DeliveryDispatched);

        $useCase = new DispatchDeliveryUseCase($orderRepo, $trackingRepo);
        $result  = $useCase->execute('do-uuid-1');

        $this->assertSame('dispatched', $result->status);
    }

    // -------------------------------------------------------------------------
    // CompleteDeliveryUseCase
    // -------------------------------------------------------------------------

    public function test_complete_throws_when_order_not_found(): void
    {
        $orderRepo   = Mockery::mock(DeliveryOrderRepositoryInterface::class);
        $trackingRepo = Mockery::mock(TrackingEventRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CompleteDeliveryUseCase($orderRepo, $trackingRepo);

        $this->expectException(ModelNotFoundException::class);

        $useCase->execute('missing-id');
    }

    public function test_complete_throws_when_order_in_wrong_status(): void
    {
        // 'pending' is not in ['dispatched','in_transit']
        $order = $this->makeOrder('do-uuid-1', 'pending');

        $orderRepo   = Mockery::mock(DeliveryOrderRepositoryInterface::class);
        $trackingRepo = Mockery::mock(TrackingEventRepositoryInterface::class);

        $orderRepo->shouldReceive('findById')->andReturn($order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CompleteDeliveryUseCase($orderRepo, $trackingRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/dispatched.*in_transit/i');

        $useCase->execute('do-uuid-1');
    }

    public function test_complete_from_dispatched_transitions_to_delivered(): void
    {
        $order     = $this->makeOrder('do-uuid-1', 'dispatched');
        $delivered = (object) array_merge((array) $order, ['status' => 'delivered']);

        $orderRepo = Mockery::mock(DeliveryOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->andReturn($order, $delivered);
        $orderRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $data['status'] === 'delivered');

        $trackingRepo = Mockery::mock(TrackingEventRepositoryInterface::class);
        $trackingRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['event_type'] === 'delivered')
            ->andReturn((object) []);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof DeliveryCompleted);

        $useCase = new CompleteDeliveryUseCase($orderRepo, $trackingRepo);
        $result  = $useCase->execute('do-uuid-1');

        $this->assertSame('delivered', $result->status);
    }

    public function test_complete_from_in_transit_also_transitions_to_delivered(): void
    {
        $order     = $this->makeOrder('do-uuid-1', 'in_transit');
        $delivered = (object) array_merge((array) $order, ['status' => 'delivered']);

        $orderRepo = Mockery::mock(DeliveryOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->andReturn($order, $delivered);
        $orderRepo->shouldReceive('update')->once();

        $trackingRepo = Mockery::mock(TrackingEventRepositoryInterface::class);
        $trackingRepo->shouldReceive('create')->once()->andReturn((object) []);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CompleteDeliveryUseCase($orderRepo, $trackingRepo);
        $result  = $useCase->execute('do-uuid-1');

        $this->assertSame('delivered', $result->status);
    }
}
