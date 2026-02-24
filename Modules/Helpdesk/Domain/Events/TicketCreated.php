<?php

namespace Modules\Helpdesk\Domain\Events;

class TicketCreated
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $tenantId,
        public readonly string $subject,
        public readonly string $priority,
    ) {}
}
