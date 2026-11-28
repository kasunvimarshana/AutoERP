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

enum StockCountType: string
{
    case StockCount = 'stock_count';
    case CycleCount = 'cycle_count';
}
