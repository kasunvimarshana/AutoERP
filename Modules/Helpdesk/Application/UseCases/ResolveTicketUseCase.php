<?php

namespace Modules\Helpdesk\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Domain\Contracts\TicketRepositoryInterface;
use Modules\Helpdesk\Domain\Events\TicketResolved;

class ResolveTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {}

    public function execute(string $ticketId, string $resolverId, ?string $resolutionNotes = null): object
    {
        return DB::transaction(function () use ($ticketId, $resolverId, $resolutionNotes) {
            $ticket = $this->ticketRepo->findById($ticketId);

            if (! $ticket) {
                throw new DomainException('Ticket not found.');
            }

            if (! in_array($ticket->status, ['new', 'open', 'in_progress'], true)) {
                throw new DomainException('Only new, open, or in-progress tickets can be resolved.');
            }

            $updated = $this->ticketRepo->update($ticketId, [
                'status'           => 'resolved',
                'resolver_id'      => $resolverId,
                'resolution_notes' => $resolutionNotes,
                'resolved_at'      => now(),
            ]);

            Event::dispatch(new TicketResolved($ticketId, $ticket->tenant_id, $resolverId));

            return $updated;
        });
    }
}
