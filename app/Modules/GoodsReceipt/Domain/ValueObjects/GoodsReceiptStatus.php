<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\ValueObjects;

class GoodsReceiptStatus
{
    const DRAFT              = 'draft';
    const PENDING            = 'pending';
    const PARTIALLY_RECEIVED = 'partially_received';
    const FULLY_RECEIVED     = 'fully_received';
    const APPROVED           = 'approved';
    const UNDER_INSPECTION   = 'under_inspection';
    const INSPECTED          = 'inspected';
    const PUT_AWAY           = 'put_away';
    const CANCELLED          = 'cancelled';

    public static function values(): array
    {
        return [
            'draft', 'pending', 'partially_received', 'fully_received',
            'approved', 'under_inspection', 'inspected', 'put_away', 'cancelled',
        ];
    }
}
