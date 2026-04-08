<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\ValueObjects;

class DispatchStatus
{
    const DRAFT      = 'draft';
    const CONFIRMED  = 'confirmed';
    const IN_TRANSIT = 'in_transit';
    const DELIVERED  = 'delivered';
    const CANCELLED  = 'cancelled';

    public static function values(): array
    {
        return ['draft', 'confirmed', 'in_transit', 'delivered', 'cancelled'];
    }
}
