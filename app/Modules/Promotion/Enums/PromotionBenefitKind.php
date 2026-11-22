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

enum PromotionBenefitKind: string
{
    case Direct = 'direct';
    case Earned = 'earned';
}
