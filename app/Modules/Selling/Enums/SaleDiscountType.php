<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Selling\Enums;

enum SaleDiscountType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
