<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Enums;

enum InvoiceCalculationMethod: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
