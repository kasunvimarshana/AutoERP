<?php

declare(strict_types=1);

namespace Modules\CRM\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case PROPOSAL = 'proposal';
    case NEGOTIATION = 'negotiation';
    case WON = 'won';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New Lead',
            self::CONTACTED => 'Contacted',
            self::QUALIFIED => 'Qualified',
            self::PROPOSAL => 'Proposal Sent',
            self::NEGOTIATION => 'In Negotiation',
            self::WON => 'Won (Converted)',
            self::LOST => 'Lost',
        };
    }
}
