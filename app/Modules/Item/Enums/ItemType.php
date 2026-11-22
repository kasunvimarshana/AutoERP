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

enum ItemType: string
{
    case Stock = 'stock';
    case NonStock = 'non_stock';
    case Service = 'service';
    case Labour = 'labour';
    case Asset = 'asset';
    case Consumable = 'consumable';
    case Package = 'package';
    case Combo = 'combo';
}
