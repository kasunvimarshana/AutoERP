<?php

declare(strict_types=1);

namespace Modules\CRM\Enums;

enum OpportunityStage: string
{
    case PROSPECTING = 'prospecting';
    case QUALIFICATION = 'qualification';
    case NEEDS_ANALYSIS = 'needs_analysis';
    case PROPOSAL = 'proposal';
    case NEGOTIATION = 'negotiation';
    case CLOSED_WON = 'closed_won';
    case CLOSED_LOST = 'closed_lost';

    public function label(): string
    {
        return match ($this) {
            self::PROSPECTING => 'Prospecting',
            self::QUALIFICATION => 'Qualification',
            self::NEEDS_ANALYSIS => 'Needs Analysis',
            self::PROPOSAL => 'Proposal/Price Quote',
            self::NEGOTIATION => 'Negotiation/Review',
            self::CLOSED_WON => 'Closed Won',
            self::CLOSED_LOST => 'Closed Lost',
        };
    }

    public function probability(): int
    {
        return match ($this) {
            self::PROSPECTING => 10,
            self::QUALIFICATION => 20,
            self::NEEDS_ANALYSIS => 40,
            self::PROPOSAL => 60,
            self::NEGOTIATION => 80,
            self::CLOSED_WON => 100,
            self::CLOSED_LOST => 0,
        };
    }

    public function isWon(): bool
    {
        return $this === self::CLOSED_WON;
    }

    public function isLost(): bool
    {
        return $this === self::CLOSED_LOST;
    }

    public function isOpen(): bool
    {
        return ! $this->isWon() && ! $this->isLost();
    }
}
