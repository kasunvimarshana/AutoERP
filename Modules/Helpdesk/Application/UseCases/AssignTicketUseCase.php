<?php

namespace Modules\Helpdesk\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Domain\Contracts\TicketRepositoryInterface;
use Modules\Helpdesk\Domain\Events\TicketAssigned;

class AssignTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {}

    public function execute(string $ticketId, string $assigneeId): object
    {
        return DB::transaction(function () use ($ticketId, $assigneeId) {
            $ticket = $this->ticketRepo->findById($ticketId);

            if (! $ticket) {
                throw new DomainException('Ticket not found.');
            }

            if (in_array($ticket->status, ['resolved', 'closed'], true)) {
                throw new DomainException('Cannot assign a resolved or closed ticket.');
            }

            $updated = $this->ticketRepo->update($ticketId, [
                'assigned_to' => $assigneeId,
                'status'      => 'open',
            ]);

            Event::dispatch(new TicketAssigned($ticketId, $ticket->tenant_id, $assigneeId));

            return $updated;
        });
    }
}
