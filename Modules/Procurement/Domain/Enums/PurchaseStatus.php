<?php
declare(strict_types=1);
namespace Modules\Procurement\Domain\Enums;
enum PurchaseStatus: string {
    case DRAFT     = 'draft';
    case SENT      = 'sent';
    case CONFIRMED = 'confirmed';
    case RECEIVED  = 'received';
    case PARTIAL   = 'partial';
    case CANCELLED = 'cancelled';
    case BILLED    = 'billed';
    public function label(): string {
        return match($this) {
            self::DRAFT     => 'Draft',
            self::SENT      => 'Sent to Vendor',
            self::CONFIRMED => 'Confirmed',
            self::RECEIVED  => 'Fully Received',
            self::PARTIAL   => 'Partially Received',
            self::CANCELLED => 'Cancelled',
            self::BILLED    => 'Billed',
        };
    }
    public function canReceiveGoods(): bool {
        return in_array($this, [self::CONFIRMED, self::PARTIAL]);
    }
}
