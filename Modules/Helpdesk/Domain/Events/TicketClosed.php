<?php

namespace Modules\Helpdesk\Domain\Events;

class TicketClosed
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $tenantId,
    ) {}
}
