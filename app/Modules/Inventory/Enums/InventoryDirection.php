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

enum InventoryDirection: string
{
    case In = 'in';
    case Out = 'out';
    case Neutral = 'neutral';
}
