<?php

namespace Modules\Helpdesk\Domain\Enums;

enum TicketStatus: string
{
    case New        = 'new';
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';
}
