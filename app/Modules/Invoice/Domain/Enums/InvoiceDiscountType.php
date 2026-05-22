<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Enums;

enum InvoiceDiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
