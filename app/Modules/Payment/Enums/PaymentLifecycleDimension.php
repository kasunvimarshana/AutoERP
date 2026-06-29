<?php

declare(strict_types=1);

namespace Modules\Payment\Enums;

enum PaymentLifecycleDimension: string
{
    case Document = 'document';
    case Posting = 'posting';
    case Allocation = 'allocation';
    case Instrument = 'instrument';
}
