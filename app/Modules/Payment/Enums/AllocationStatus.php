<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Enums;

enum AllocationStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Reversed = 'reversed';
    case Void = 'void';
}
