<?php

namespace App\Enums;

enum PricingType: string
{
    case Flat = 'flat';
    case Percentage = 'percentage';
    case Tiered = 'tiered';
    case RuleBased = 'rule_based';
}
