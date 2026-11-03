<?php

declare(strict_types=1);

namespace Modules\Promotion\Enums;

enum PromotionDiscountType: string
{
    case Free = 'free';
    case Percentage = 'percentage';
}
