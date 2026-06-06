<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum BatchStatus: string
{
    case Active = 'active';
    case Quarantined = 'quarantined';
    case Expired = 'expired';
    case Blocked = 'blocked';
    case Closed = 'closed';
}
