<?php

namespace Modules\Helpdesk\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Domain\Contracts\TicketRepositoryInterface;
use Modules\Helpdesk\Domain\Events\TicketCreated;

class CreateTicketUseCase
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $ticket = $this->ticketRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'category_id' => $data['category_id'] ?? null,
                'subject'     => $data['subject'],
                'description' => $data['description'] ?? null,
                'reporter_id' => $data['reporter_id'],
                'assigned_to' => null,
                'priority'    => $data['priority'] ?? 'medium',
                'status'      => 'new',
                'sla_due_at'  => $data['sla_due_at'] ?? null,
            ]);

            Event::dispatch(new TicketCreated(
                $ticket->id,
                $ticket->tenant_id,
                $ticket->subject,
                $ticket->priority,
            ));

            return $ticket;
        });
    }
}
