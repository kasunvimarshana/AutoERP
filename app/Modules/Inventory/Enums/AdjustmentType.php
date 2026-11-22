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

enum AdjustmentType: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
    case Recount = 'recount';
    case Damage = 'damage';
    case Expiry = 'expiry';
    case OpeningBalance = 'opening_balance';
}
