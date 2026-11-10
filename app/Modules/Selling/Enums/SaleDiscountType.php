<?php

declare(strict_types=1);

namespace Modules\Selling\Enums;

enum SaleDiscountType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
