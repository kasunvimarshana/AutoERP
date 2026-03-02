<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Billed = 'billed';
    case Cancelled = 'cancelled';

    public function isCancellable(): bool
    {
        return in_array($this, [
            self::Draft,
            self::Confirmed,
            self::PartiallyReceived,
        ], true);
    }

    public function isReceivable(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::PartiallyReceived,
        ], true);
    }
}
