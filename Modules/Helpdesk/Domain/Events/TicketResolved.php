<?php

namespace Modules\Helpdesk\Domain\Events;

class TicketResolved
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $tenantId,
        public readonly string $resolverId,
    ) {}
}
