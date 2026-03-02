<?php

declare(strict_types=1);

namespace Modules\Crm\Domain\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function isClosedWon(): bool
    {
        return $this === self::Won;
    }

    public function isClosedLost(): bool
    {
        return $this === self::Lost;
    }

    public function isClosed(): bool
    {
        return $this->isClosedWon() || $this->isClosedLost();
    }
}
