<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Domain\ValueObjects;

class PurchaseOrderLineStatus
{
    const OPEN               = 'open';
    const PARTIALLY_RECEIVED = 'partially_received';
    const FULLY_RECEIVED     = 'fully_received';
    const CANCELLED          = 'cancelled';

    public static function values(): array
    {
        return ['open', 'partially_received', 'fully_received', 'cancelled'];
    }
}
