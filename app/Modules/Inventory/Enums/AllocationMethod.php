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

enum AllocationMethod: string
{
    case FIFO = 'fifo';
    case FEFO = 'fefo';
    case Batch = 'batch';
    case Serial = 'serial';
    case Manual = 'manual';
}
