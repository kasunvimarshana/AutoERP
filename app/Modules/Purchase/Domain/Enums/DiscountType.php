<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
