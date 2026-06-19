<?php

declare(strict_types=1);

namespace Modules\Sales\Enums;

enum SalesAllocationStatus: string
{
    case Active = 'active';
    case PartiallyReleased = 'partially_released';
    case Released = 'released';
    case Issued = 'issued';
    case Cancelled = 'cancelled';
}
