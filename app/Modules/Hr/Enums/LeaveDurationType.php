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

enum LeaveDurationType: string
{
    case FullDay = 'full_day';
    case HalfDayMorning = 'half_day_morning';
    case HalfDayAfternoon = 'half_day_afternoon';
    case CustomHours = 'custom_hours';
}
