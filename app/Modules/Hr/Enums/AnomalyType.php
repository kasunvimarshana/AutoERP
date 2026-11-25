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

enum AnomalyType: string
{
    case MissingOut = 'missing_out';
    case MissingIn = 'missing_in';
    case UnpairedBreak = 'unpaired_break';
    case ExcessiveHours = 'excessive_hours';
    case ShortShift = 'short_shift';
    case GeofenceViolation = 'geofence_violation';
}
