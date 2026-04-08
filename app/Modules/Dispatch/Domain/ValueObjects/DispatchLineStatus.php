<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\ValueObjects;

class DispatchLineStatus
{
    const PENDING   = 'pending';
    const PICKED    = 'picked';
    const PACKED    = 'packed';
    const SHIPPED   = 'shipped';
    const CANCELLED = 'cancelled';

    public static function values(): array
    {
        return ['pending', 'picked', 'packed', 'shipped', 'cancelled'];
    }
}
