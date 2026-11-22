<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Item\Enums;

enum ItemPriceType: string
{
    case Purchase = 'purchase';
    case Sales = 'sales';
    case Service = 'service';
    case Rental = 'rental';
}
