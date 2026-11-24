<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Enums;

enum AttendanceLockStatus: string
{
    case Open = 'open';
    case Locked = 'locked';
    case Finalized = 'finalized';
}
