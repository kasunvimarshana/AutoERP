<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Enums;

enum InvoicePostingPlanStatus: string
{
    case Prepared = 'prepared';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
