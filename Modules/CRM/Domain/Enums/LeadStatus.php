<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Enums;
enum LeadStatus: string {
    case NEW          = 'new';
    case CONTACTED    = 'contacted';
    case QUALIFIED    = 'qualified';
    case PROPOSAL     = 'proposal';
    case NEGOTIATION  = 'negotiation';
    case WON          = 'won';
    case LOST         = 'lost';
    case UNQUALIFIED  = 'unqualified';
    public function label(): string {
        return match($this) {
            self::NEW         => 'New',
            self::CONTACTED   => 'Contacted',
            self::QUALIFIED   => 'Qualified',
            self::PROPOSAL    => 'Proposal Sent',
            self::NEGOTIATION => 'In Negotiation',
            self::WON         => 'Won',
            self::LOST        => 'Lost',
            self::UNQUALIFIED => 'Unqualified',
        };
    }
    public function isClosedWon(): bool { return $this === self::WON; }
    public function isClosedLost(): bool { return in_array($this, [self::LOST, self::UNQUALIFIED]); }
    public function isClosed(): bool { return $this->isClosedWon() || $this->isClosedLost(); }
}
