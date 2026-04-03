<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\ValueObjects;

class PriceListType
{
    public const SALE             = 'sale';
    public const PURCHASE         = 'purchase';
    public const SPECIAL          = 'special';
    public const PROMOTIONAL      = 'promotional';
    public const CUSTOMER_SPECIFIC = 'customer_specific';
    public const WHOLESALE        = 'wholesale';
    public const RETAIL           = 'retail';

    public static function values(): array
    {
        return ['sale', 'purchase', 'special', 'promotional', 'customer_specific', 'wholesale', 'retail'];
    }
}
