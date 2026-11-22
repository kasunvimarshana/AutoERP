<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Finance\Enums;

enum JournalType: string
{
    case General = 'general';
    case Invoice = 'invoice';
    case Payment = 'payment';
    case Contra = 'contra';
    case Adjustment = 'adjustment';
    case Reversal = 'reversal';
    case Opening = 'opening';
}
