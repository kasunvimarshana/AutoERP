<?php

namespace App\Contracts\Pricing;

interface PricingEngineInterface
{
    public function calculate(array $context): \Brick\Math\BigDecimal|string;
}
