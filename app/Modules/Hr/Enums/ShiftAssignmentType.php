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

enum ShiftAssignmentType: string
{
    case Recurring = 'recurring';
    case DateRange = 'date_range';
    case SpecificDate = 'specific_date';
}
