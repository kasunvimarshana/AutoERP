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

enum ShiftReallocationReason: string
{
    case SickCover = 'sick_cover';
    case EmergencyCover = 'emergency_cover';
    case SurgeDemand = 'surge_demand';
    case OperationalNeed = 'operational_need';
}
