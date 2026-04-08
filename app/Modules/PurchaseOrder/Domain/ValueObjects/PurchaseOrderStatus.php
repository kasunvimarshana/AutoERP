<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Domain\ValueObjects;

class PurchaseOrderStatus
{
    const DRAFT             = 'draft';
    const SUBMITTED         = 'submitted';
    const APPROVED          = 'approved';
    const PARTIALLY_RECEIVED = 'partially_received';
    const FULLY_RECEIVED    = 'fully_received';
    const CANCELLED         = 'cancelled';
    const CLOSED            = 'closed';

    public static function values(): array
    {
        return ['draft', 'submitted', 'approved', 'partially_received', 'fully_received', 'cancelled', 'closed'];
    }
}
