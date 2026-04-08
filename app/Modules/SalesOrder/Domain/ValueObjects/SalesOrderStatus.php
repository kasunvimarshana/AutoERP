<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\ValueObjects;

class SalesOrderStatus
{
    const DRAFT     = 'draft';
    const CONFIRMED = 'confirmed';
    const PICKING   = 'picking';
    const PACKING   = 'packing';
    const SHIPPED   = 'shipped';
    const DELIVERED = 'delivered';
    const CANCELLED = 'cancelled';

    public static function values(): array
    {
        return ['draft', 'confirmed', 'picking', 'packing', 'shipped', 'delivered', 'cancelled'];
    }
}
