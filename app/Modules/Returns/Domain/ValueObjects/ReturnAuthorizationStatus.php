<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

class ReturnAuthorizationStatus
{
    const PENDING   = 'pending';
    const APPROVED  = 'approved';
    const EXPIRED   = 'expired';
    const CANCELLED = 'cancelled';

    public static function values(): array
    {
        return ['pending', 'approved', 'expired', 'cancelled'];
    }
}
