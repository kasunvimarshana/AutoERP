<?php

namespace Tests\Unit\FieldService;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\FieldService\Application\UseCases\AssignTechnicianUseCase;
use Modules\FieldService\Application\UseCases\CompleteServiceOrderUseCase;
use Modules\FieldService\Application\UseCases\CreateServiceOrderUseCase;
use Modules\FieldService\Domain\Contracts\ServiceOrderRepositoryInterface;
use Modules\FieldService\Domain\Events\ServiceOrderAssigned;
use Modules\FieldService\Domain\Events\ServiceOrderCompleted;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FieldService use cases.
 *
 * Covers service order creation, technician assignment guards,
 * completion status validation, and domain event dispatch.
 */
class FieldServiceUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeOrder(string $status = 'new'): object
    {
        return (object) [
            'id'           => 'order-uuid-1',
            'tenant_id'    => 'tenant-uuid-1',
            'status'       => $status,
            'title'        => 'Fix HVAC unit',
            'duration_hours' => '0.00000000',
            'labor_cost'   => '0.00000000',
            'parts_cost'   => '0.00000000',
            'resolution_notes' => null,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateServiceOrderUseCase
    // -------------------------------------------------------------------------

    public function test_creates_service_order_with_new_status(): void
    {
        $order = $this->makeOrder('new');

        $repo = Mockery::mock(ServiceOrderRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'new' && $data['tenant_id'] === 'tenant-uuid-1')
            ->andReturn($order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereYear')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $useCase = new CreateServiceOrderUseCase($repo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'title'     => 'Fix HVAC unit',
        ]);

        $this->assertSame('new', $result->status);
    }

    // -------------------------------------------------------------------------
    // AssignTechnicianUseCase
    // -------------------------------------------------------------------------

    public function test_assign_throws_when_order_not_found(): void
    {
        $repo = Mockery::mock(ServiceOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new AssignTechnicianUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', 'tech-uuid-1');
    }

    public function test_assign_throws_when_order_already_done(): void
    {
        $repo = Mockery::mock(ServiceOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makeOrder('done'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new AssignTechnicianUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/new or assigned/i');

        $useCase->execute('order-uuid-1', 'tech-uuid-1');
    }

    public function test_assign_transitions_to_assigned_and_dispatches_event(): void
    {
        $order    = $this->makeOrder('new');
        $assigned = (object) array_merge((array) $order, [
            'status'        => 'assigned',
            'technician_id' => 'tech-uuid-1',
        ]);

        $repo = Mockery::mock(ServiceOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($order);
        $repo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'assigned' && $data['technician_id'] === 'tech-uuid-1')
            ->once()
            ->andReturn($assigned);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ServiceOrderAssigned
                && $event->technicianId === 'tech-uuid-1');

        $useCase = new AssignTechnicianUseCase($repo);
        $result  = $useCase->execute('order-uuid-1', 'tech-uuid-1');

        $this->assertSame('assigned', $result->status);
    }

    // -------------------------------------------------------------------------
    // CompleteServiceOrderUseCase
    // -------------------------------------------------------------------------

    public function test_complete_throws_when_order_not_found(): void
    {
        $repo = Mockery::mock(ServiceOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CompleteServiceOrderUseCase($repo);

        $this->expectException(DomainException::class);

        $useCase->execute('missing-id');
    }

    public function test_complete_throws_when_order_is_new(): void
    {
        $repo = Mockery::mock(ServiceOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makeOrder('new'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new CompleteServiceOrderUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/assigned or in-progress/i');

        $useCase->execute('order-uuid-1');
    }

    public function test_complete_transitions_to_done_and_dispatches_event(): void
    {
        $order = $this->makeOrder('assigned');
        $done  = (object) array_merge((array) $order, [
            'status'       => 'done',
            'completed_at' => now(),
        ]);

        $repo = Mockery::mock(ServiceOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($order);
        $repo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'done')
            ->once()
            ->andReturn($done);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ServiceOrderCompleted);

        $useCase = new CompleteServiceOrderUseCase($repo);
        $result  = $useCase->execute('order-uuid-1', ['duration_hours' => '2.5']);

        $this->assertSame('done', $result->status);
    }
}
