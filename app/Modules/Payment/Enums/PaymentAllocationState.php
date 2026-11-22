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

enum PaymentAllocationState: string
{
    case Unallocated = 'unallocated';
    case PartiallyAllocated = 'partially_allocated';
    case FullyAllocated = 'fully_allocated';
}
