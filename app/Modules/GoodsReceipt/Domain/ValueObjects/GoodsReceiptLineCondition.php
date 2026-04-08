<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\ValueObjects;

class GoodsReceiptLineCondition
{
    const GOOD       = 'good';
    const DAMAGED    = 'damaged';
    const EXPIRED    = 'expired';
    const QUARANTINE = 'quarantine';

    public static function values(): array
    {
        return ['good', 'damaged', 'expired', 'quarantine'];
    }
}
