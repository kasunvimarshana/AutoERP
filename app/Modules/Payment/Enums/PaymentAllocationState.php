<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentAllocationState: string
{
    case Unallocated = 'unallocated';
    case PartiallyAllocated = 'partially_allocated';
    case FullyAllocated = 'fully_allocated';
}
