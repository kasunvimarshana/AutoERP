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

enum PromotionEventType: string
{
    case Qualification = 'qualification';
    case Redemption = 'redemption';
    case Correction = 'correction';
}
