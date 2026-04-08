<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\ValueObjects;

class PricingMethod
{
    public const FIXED               = 'fixed';
    public const PERCENTAGE_DISCOUNT = 'percentage_discount';
    public const PERCENTAGE_MARKUP   = 'percentage_markup';
    public const FORMULA             = 'formula';

    public static function values(): array
    {
        return ['fixed', 'percentage_discount', 'percentage_markup', 'formula'];
    }
}
