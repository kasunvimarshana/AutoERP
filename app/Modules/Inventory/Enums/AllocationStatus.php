<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum AllocationStatus: string
{
    case Active = 'active';
    case Issued = 'issued';
    case Released = 'released';
    case Cancelled = 'cancelled';
}
