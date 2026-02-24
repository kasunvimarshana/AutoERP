<?php

namespace Tests\Unit\Helpdesk;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Helpdesk\Application\UseCases\AssignTicketUseCase;
use Modules\Helpdesk\Application\UseCases\CloseTicketUseCase;
use Modules\Helpdesk\Application\UseCases\CreateTicketUseCase;
use Modules\Helpdesk\Application\UseCases\ResolveTicketUseCase;
use Modules\Helpdesk\Domain\Contracts\TicketRepositoryInterface;
use Modules\Helpdesk\Domain\Events\TicketAssigned;
use Modules\Helpdesk\Domain\Events\TicketClosed;
use Modules\Helpdesk\Domain\Events\TicketCreated;
use Modules\Helpdesk\Domain\Events\TicketResolved;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Helpdesk module use cases.
 *
 * Covers ticket creation with event dispatch, assign/resolve/close lifecycle,
 * status guards, and domain event assertions.
 */
class HelpdeskUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTicket(string $status = 'new'): object
    {
        return (object) [
            'id'          => 'ticket-uuid-1',
            'tenant_id'   => 'tenant-uuid-1',
            'subject'     => 'Login issue',
            'priority'    => 'high',
            'status'      => $status,
            'assigned_to' => null,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateTicketUseCase
    // -------------------------------------------------------------------------

    public function test_create_ticket_sets_status_new_and_dispatches_event(): void
    {
        $ticket     = $this->makeTicket('new');
        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);

        $ticketRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'new' && $data['priority'] === 'high')
            ->andReturn($ticket);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof TicketCreated
                && $event->priority === 'high');

        $useCase = new CreateTicketUseCase($ticketRepo);
        $result  = $useCase->execute([
            'tenant_id'   => 'tenant-uuid-1',
            'reporter_id' => 'user-uuid-1',
            'subject'     => 'Login issue',
            'priority'    => 'high',
        ]);

        $this->assertSame('new', $result->status);
    }

    // -------------------------------------------------------------------------
    // AssignTicketUseCase
    // -------------------------------------------------------------------------

    public function test_assign_throws_when_ticket_not_found(): void
    {
        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);
        $ticketRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new AssignTicketUseCase($ticketRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id', 'agent-uuid-1');
    }

    public function test_assign_throws_when_ticket_is_resolved(): void
    {
        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);
        $ticketRepo->shouldReceive('findById')->andReturn($this->makeTicket('resolved'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new AssignTicketUseCase($ticketRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/resolved or closed/i');

        $useCase->execute('ticket-uuid-1', 'agent-uuid-1');
    }

    public function test_assign_transitions_to_open_and_dispatches_event(): void
    {
        $ticket   = $this->makeTicket('new');
        $assigned = (object) array_merge((array) $ticket, [
            'status'      => 'open',
            'assigned_to' => 'agent-uuid-1',
        ]);

        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);
        $ticketRepo->shouldReceive('findById')->andReturn($ticket);
        $ticketRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'open' && $data['assigned_to'] === 'agent-uuid-1')
            ->once()
            ->andReturn($assigned);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof TicketAssigned
                && $event->assigneeId === 'agent-uuid-1');

        $useCase = new AssignTicketUseCase($ticketRepo);
        $result  = $useCase->execute('ticket-uuid-1', 'agent-uuid-1');

        $this->assertSame('open', $result->status);
    }

    // -------------------------------------------------------------------------
    // ResolveTicketUseCase
    // -------------------------------------------------------------------------

    public function test_resolve_throws_when_ticket_not_found(): void
    {
        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);
        $ticketRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ResolveTicketUseCase($ticketRepo);

        $this->expectException(DomainException::class);

        $useCase->execute('missing-id', 'agent-uuid-1');
    }

    public function test_resolve_throws_when_ticket_already_closed(): void
    {
        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);
        $ticketRepo->shouldReceive('findById')->andReturn($this->makeTicket('closed'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ResolveTicketUseCase($ticketRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/new, open, or in-progress/i');

        $useCase->execute('ticket-uuid-1', 'agent-uuid-1');
    }

    public function test_resolve_transitions_to_resolved_and_dispatches_event(): void
    {
        $ticket   = $this->makeTicket('open');
        $resolved = (object) array_merge((array) $ticket, ['status' => 'resolved']);

        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);
        $ticketRepo->shouldReceive('findById')->andReturn($ticket);
        $ticketRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'resolved')
            ->once()
            ->andReturn($resolved);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof TicketResolved
                && $event->resolverId === 'agent-uuid-1');

        $useCase = new ResolveTicketUseCase($ticketRepo);
        $result  = $useCase->execute('ticket-uuid-1', 'agent-uuid-1', 'Password reset completed.');

        $this->assertSame('resolved', $result->status);
    }

    // -------------------------------------------------------------------------
    // CloseTicketUseCase
    // -------------------------------------------------------------------------

    public function test_close_throws_when_ticket_not_found(): void
    {
        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);
        $ticketRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CloseTicketUseCase($ticketRepo);

        $this->expectException(DomainException::class);

        $useCase->execute('missing-id');
    }

    public function test_close_throws_when_already_closed(): void
    {
        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);
        $ticketRepo->shouldReceive('findById')->andReturn($this->makeTicket('closed'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new CloseTicketUseCase($ticketRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already closed/i');

        $useCase->execute('ticket-uuid-1');
    }

    public function test_close_transitions_to_closed_and_dispatches_event(): void
    {
        $ticket = $this->makeTicket('resolved');
        $closed = (object) array_merge((array) $ticket, ['status' => 'closed']);

        $ticketRepo = Mockery::mock(TicketRepositoryInterface::class);
        $ticketRepo->shouldReceive('findById')->andReturn($ticket);
        $ticketRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'closed')
            ->once()
            ->andReturn($closed);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof TicketClosed
                && $event->ticketId === 'ticket-uuid-1');

        $useCase = new CloseTicketUseCase($ticketRepo);
        $result  = $useCase->execute('ticket-uuid-1');

        $this->assertSame('closed', $result->status);
    }
}
