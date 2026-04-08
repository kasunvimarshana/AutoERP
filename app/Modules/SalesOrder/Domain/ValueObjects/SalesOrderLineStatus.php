<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\ValueObjects;

class SalesOrderLineStatus
{
    const PENDING    = 'pending';
    const PICKING    = 'picking';
    const PACKED     = 'packed';
    const DISPATCHED = 'dispatched';
    const CANCELLED  = 'cancelled';

    public static function values(): array
    {
        return ['pending', 'picking', 'packed', 'dispatched', 'cancelled'];
    }
}
