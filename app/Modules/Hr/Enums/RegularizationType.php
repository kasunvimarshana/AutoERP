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

enum RegularizationType: string
{
    case MissingIn = 'missing_in';
    case MissingOut = 'missing_out';
    case BothMissing = 'both_missing';
    case IncorrectTime = 'incorrect_time';
    case ForgotToPunch = 'forgot_to_punch';
    case OnDuty = 'on_duty';
}
