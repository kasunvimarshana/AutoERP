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

enum ChequePrintStatus: string
{
    case Previewed = 'previewed';
    case Printed = 'printed';
    case Cancelled = 'cancelled';
}
