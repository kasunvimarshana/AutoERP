<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Enums;

enum BatchStatus: string
{
    case Active = 'active';
    case Quarantined = 'quarantined';
    case Expired = 'expired';
    case Blocked = 'blocked';
    case Closed = 'closed';
}
