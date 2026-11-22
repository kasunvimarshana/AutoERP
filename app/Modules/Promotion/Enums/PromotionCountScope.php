<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Promotion\Enums;

enum PromotionCountScope: string
{
    case Customer = 'customer';
    case Vehicle = 'vehicle';
}
