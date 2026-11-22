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

enum PaymentPostingStatus: string
{
    case NotPosted = 'not_posted';
    case Posting = 'posting';
    case Posted = 'posted';
    case Failed = 'failed';
    case Reversed = 'reversed';
}
