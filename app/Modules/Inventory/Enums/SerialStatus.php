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

enum SerialStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Issued = 'issued';
    case Sold = 'sold';
    case Returned = 'returned';
    case Damaged = 'damaged';
    case Blocked = 'blocked';
}
