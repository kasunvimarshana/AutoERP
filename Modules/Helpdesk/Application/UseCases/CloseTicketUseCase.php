<?php

namespace Modules\Helpdesk\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Domain\Contracts\TicketRepositoryInterface;
use Modules\Helpdesk\Domain\Events\TicketClosed;

class CloseTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {}

    public function execute(string $ticketId): object
    {
        return DB::transaction(function () use ($ticketId) {
            $ticket = $this->ticketRepo->findById($ticketId);

            if (! $ticket) {
                throw new DomainException('Ticket not found.');
            }

            if ($ticket->status === 'closed') {
                throw new DomainException('Ticket is already closed.');
            }

            $updated = $this->ticketRepo->update($ticketId, [
                'status'    => 'closed',
                'closed_at' => now(),
            ]);

            Event::dispatch(new TicketClosed($ticketId, $ticket->tenant_id));

            return $updated;
        });
    }
}
