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

enum PaymentLifecycleDimension: string
{
    case Document = 'document';
    case Posting = 'posting';
    case Allocation = 'allocation';
    case Instrument = 'instrument';
}
