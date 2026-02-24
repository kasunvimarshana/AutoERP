<?php

namespace Modules\POS\Domain\Enums;

enum DiscountType: string
{
    case PERCENTAGE   = 'percentage';
    case FIXED_AMOUNT = 'fixed_amount';
}
