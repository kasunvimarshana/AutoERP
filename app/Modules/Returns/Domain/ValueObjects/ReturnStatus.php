<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

class ReturnStatus
{
    const DRAFT              = 'draft';
    const PENDING_INSPECTION = 'pending_inspection';
    const APPROVED           = 'approved';
    const REJECTED           = 'rejected';
    const COMPLETED          = 'completed';
    const CANCELLED          = 'cancelled';

    public static function values(): array
    {
        return ['draft', 'pending_inspection', 'approved', 'rejected', 'completed', 'cancelled'];
    }
}
