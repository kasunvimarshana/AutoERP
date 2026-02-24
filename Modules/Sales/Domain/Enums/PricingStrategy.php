<?php
namespace Modules\Sales\Domain\Enums;

enum PricingStrategy: string
{
    case Flat             = 'flat';
    case PercentageDiscount = 'percentage_discount';
}
