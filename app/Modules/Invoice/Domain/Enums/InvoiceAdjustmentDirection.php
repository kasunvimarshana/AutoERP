<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Enums;

enum InvoiceAdjustmentDirection: string
{
    case Add = 'add';
    case Subtract = 'subtract';
}
