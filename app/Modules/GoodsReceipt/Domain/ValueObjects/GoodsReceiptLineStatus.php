<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\ValueObjects;

class GoodsReceiptLineStatus
{
    const PENDING            = 'pending';
    const ACCEPTED           = 'accepted';
    const REJECTED           = 'rejected';
    const PARTIALLY_ACCEPTED = 'partially_accepted';

    public static function values(): array
    {
        return ['pending', 'accepted', 'rejected', 'partially_accepted'];
    }
}
