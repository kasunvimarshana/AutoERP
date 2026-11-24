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

enum PunchType: string
{
    case In = 'in';
    case Out = 'out';
    case BreakOut = 'break_out';
    case BreakIn = 'break_in';
    case MealOut = 'meal_out';
    case MealIn = 'meal_in';
}
